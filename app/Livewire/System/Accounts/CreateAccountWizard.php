<?php

declare(strict_types=1);

namespace App\Livewire\System\Accounts;

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Transition;
use Livewire\Component;

/**
 * System admin: wizard to create a new account.
 * Step 1: Type (organization | individual) + Name.
 * Step 2: Owner (optional User).
 * Step 3: Attach organization (optional, only for type=organization) + Preview & Submit.
 */
final class CreateAccountWizard extends Component
{
    public int $step = 1;

    public int $totalSteps = 3;

    public string $type = 'organization';

    public string $name = '';

    public string $owner_user_id = '';

    public string $attach_organization_id = '';

    #[Layout('layouts.app')]
    #[Title('Create account')]
    public function render(): View
    {
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $organizationsWithoutAccount = $this->type === 'organization'
            ? Organization::query()->whereNull('account_id')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.system.accounts.create-account-wizard', [
            'users' => $users,
            'organizationsWithoutAccount' => $organizationsWithoutAccount,
        ]);
    }

    #[Transition(type: 'forward')]
    public function nextStep(): mixed
    {
        $this->validateCurrentStep();
        if ($this->step >= $this->totalSteps) {
            return $this->save();
        }
        $this->step++;

        return null;
    }

    #[Transition(type: 'backward')]
    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    protected function validateCurrentStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'type' => 'required|in:organization,individual',
                'name' => 'required|string|max:255',
            ], [], [
                'type' => __('Customer Type'),
                'name' => __('Name'),
            ]);
        }
        if ($this->step === 2) {
            $this->validate([
                'owner_user_id' => 'nullable|exists:users,id',
            ], [], [
                'owner_user_id' => __('Owner'),
            ]);
        }
        if ($this->step === 3 && $this->type === 'organization' && $this->attach_organization_id !== '') {
            $this->validate([
                'attach_organization_id' => 'nullable|exists:organizations,id',
            ], [], [
                'attach_organization_id' => __('Organization'),
            ]);
        }
    }

    protected function save(): mixed
    {
        $this->validate([
            'type' => 'required|in:organization,individual',
            'name' => 'required|string|max:255',
            'owner_user_id' => 'nullable|exists:users,id',
            'attach_organization_id' => 'nullable|exists:organizations,id',
        ]);

        $account = Account::create([
            'type' => $this->type,
            'name' => $this->name,
            'owner_user_id' => $this->owner_user_id !== '' ? (int) $this->owner_user_id : null,
        ]);

        if ($this->type === 'organization' && $this->attach_organization_id !== '') {
            $orgId = (int) $this->attach_organization_id;
            Organization::where('id', $orgId)->update(['account_id' => $account->id]);
        }

        session()->flash('message', __('Account created successfully.'));

        return $this->redirect(route('system.accounts.show', $account), navigate: true);
    }

    public function updatedType(): void
    {
        $this->attach_organization_id = '';
    }
}
