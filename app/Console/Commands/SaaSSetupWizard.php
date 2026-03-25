<?php

namespace App\Console\Commands;

use App\Enums\OrganizationUserRole;
use App\Models\Account;
use App\Models\Organization;
use App\Models\ProductPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\task;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class SaaSSetupWizard extends Command
{
    protected $signature = 'saas:setup';

    protected $description = 'Interactive SaaS tenant setup — creates Account, Organization, User, and optional Trial';

    public function handle(): int
    {
        info('🚀 SaaS Setup Wizard');

        // =========================
        // 1. Organization + Account Info
        // =========================
        $orgData = form()
            ->text(
                name: 'name',
                label: 'Organization name',
                required: true,
                validate: ['name' => 'min:2'],
                hint: 'The display name for this tenant',
            )
            ->text(
                name: 'slug',
                label: 'Slug (URL identifier)',
                required: true,
                validate: ['slug' => 'required|alpha_dash|unique:organizations,slug'],
                hint: 'Used in URLs — lowercase, no spaces',
            )
            ->text(
                name: 'billing_email',
                label: 'Billing email (optional)',
                validate: fn (string $value): ?string => $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)
                    ? 'Must be a valid email address.'
                    : null,
                hint: 'Leave empty to use the admin user email',
            )
            ->submit();

        // =========================
        // 2. Admin User (existing or new)
        // =========================
        $userMode = select(
            label: 'Admin user setup',
            options: [
                'new' => 'Create a new user',
                'existing' => 'Assign an existing user',
            ],
        );

        $existingUser = null;

        if ($userMode === 'existing') {
            $userId = search(
                label: 'Search for existing user',
                placeholder: 'Type name or email...',
                options: fn (string $value): array => strlen($value) >= 2
                    ? User::query()
                        ->where('name', 'ilike', "%{$value}%")
                        ->orWhere('email', 'ilike', "%{$value}%")
                        ->limit(15)
                        ->get()
                        ->mapWithKeys(fn (User $u): array => [$u->id => "{$u->name} ({$u->email})"])
                        ->all()
                    : [],
                hint: 'Start typing at least 2 characters',
            );
            $existingUser = User::findOrFail($userId);
        }

        $newUserData = [];
        if ($userMode === 'new') {
            $newUserData = form()
                ->text(
                    name: 'name',
                    label: 'Admin name',
                    required: true,
                    validate: ['name' => 'min:2'],
                )
                ->text(
                    name: 'email',
                    label: 'Admin email',
                    required: true,
                    validate: ['email' => 'required|email|unique:users,email'],
                )
                ->password(
                    name: 'password',
                    label: 'Admin password',
                    required: true,
                    validate: ['password' => 'min:8'],
                    hint: 'Minimum 8 characters',
                )
                ->submit();
        }

        $adminLabel = $userMode === 'existing'
            ? "{$existingUser->name} ({$existingUser->email})"
            : "{$newUserData['name']} ({$newUserData['email']})";

        // =========================
        // 3. Subscription Plan (optional)
        // =========================
        $activePlans = ProductPlan::query()
            ->where('is_active', true)
            ->with(['product', 'prices' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $planOptions = ['none' => 'No subscription (free tier)'];

        foreach ($activePlans as $plan) {
            $monthlyPrice = $plan->prices->firstWhere('billing_cycle', 'monthly');
            $priceLabel = $monthlyPrice
                ? number_format($monthlyPrice->amount / 100, 2) . ' ' . $monthlyPrice->currency . '/month'
                : 'No monthly price';

            $planOptions[$plan->id] = "{$plan->product->name} — {$plan->name} ({$priceLabel})";
        }

        $selectedPlanId = select(
            label: 'Select a subscription plan',
            options: $planOptions,
            default: 'none',
            hint: 'Trial will be started automatically for 14 days',
        );

        $trialDays = 14;
        if ($selectedPlanId !== 'none') {
            $trialDays = (int) text(
                label: 'Trial period (days)',
                default: '14',
                validate: fn (string $value): ?string => ! is_numeric($value) || (int) $value < 0
                    ? 'Must be a non-negative number.'
                    : null,
                hint: 'Set to 0 for immediate activation without trial',
            );
        }

        // =========================
        // 4. Confirmation Summary
        // =========================
        note('📋 Review before creating:');

        $summaryRows = [
            ['Organization', $orgData['name']],
            ['Slug', $orgData['slug']],
            ['Billing Email', $orgData['billing_email'] ?: '(from admin user)'],
            ['Admin User', $adminLabel],
            ['Plan', $selectedPlanId !== 'none' ? $planOptions[$selectedPlanId] : 'None (free tier)'],
        ];

        if ($selectedPlanId !== 'none') {
            $summaryRows[] = ['Trial', $trialDays > 0 ? "{$trialDays} days" : 'No trial — immediate activation'];
        }

        table(headers: ['Setting', 'Value'], rows: $summaryRows);

        if (! confirm('Create this tenant now?', default: true)) {
            warning('Cancelled.');

            return self::SUCCESS;
        }

// =========================
// 5. Provisioning with task()
// =========================
try {
    $result = task(
        label: 'Provisioning tenant...',
        callback: function () use ($userMode, $existingUser, $newUserData, $orgData, $selectedPlanId, $trialDays) {
            return DB::transaction(function () use ($userMode, $existingUser, $newUserData, $orgData, $selectedPlanId, $trialDays) {
                $user = $userMode === 'existing'
                    ? $existingUser
                    : User::create([
                        'name' => $newUserData['name'],
                        'email' => $newUserData['email'],
                        'password' => Hash::make($newUserData['password']),
                        'email_verified_at' => now(),
                    ]);

                $account = Account::create([
                    'type' => 'organization',
                    'owner_user_id' => $user->id,
                    'name' => $orgData['name'],
                ]);

                $organization = Organization::create([
                    'account_id' => $account->id,
                    'name' => $orgData['name'],
                    'slug' => strtolower($orgData['slug']),
                    'billing_email' => $orgData['billing_email'] ?: $user->email,
                ]);

                $organization->users()->syncWithoutDetaching([
                    $user->id => [
                        'role' => OrganizationUserRole::Owner->value,
                    ],
                ]);

                $user->update([
                    'current_organization_id' => $organization->id,
                ]);

                $subscription = null;

                if ($selectedPlanId !== 'none') {
                    $plan = ProductPlan::findOrFail($selectedPlanId);
                    $subscriptionService = app(SubscriptionService::class);

                    $subscription = $trialDays > 0
                        ? $subscriptionService->startTrial(
                            account: $account,
                            plan: $plan,
                            trialEndsAt: now()->addDays($trialDays),
                        )
                        : $subscriptionService->activatePaid(
                            account: $account,
                            plan: $plan,
                            grantedBy: $user->id,
                        );
                }

                return [
                    'user' => $user,
                    'account' => $account,
                    'organization' => $organization,
                    'subscription' => $subscription,
                ];
            });
        },
    );
} catch (\Throwable $e) {
    warning("❌ Provisioning failed: {$e->getMessage()}");

    return self::FAILURE;
}
      

        // =========================
        // 6. Output Summary
        // =========================
        $resultRows = [
            ['Account ID', (string) $account->id],
            ['Account Name', $account->name],
            ['Organization ID', (string) $organization->id],
            ['Organization Slug', $organization->slug],
            ['Admin User', $user->email],
            ['User Role', OrganizationUserRole::Owner->value],
            ['Subscription', $subscription
                ? $subscription->status . ' (Plan #' . $subscription->product_plan_id . ')'
                : 'None',
            ],
        ];

        if ($subscription?->trial_ends_at) {
            $resultRows[] = ['Trial Ends', $subscription->trial_ends_at->format('Y-m-d H:i')];
        }

        table(headers: ['Entity', 'Detail'], rows: $resultRows);

        info('✅ SaaS tenant created successfully!');

        return self::SUCCESS;
    }
}
