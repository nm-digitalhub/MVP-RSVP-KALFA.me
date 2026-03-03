<?php

namespace App\Livewire\System;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\User;
use App\Services\OfficeGuy\SystemBillingService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('layouts.app')]
    #[Title('System Dashboard')]
    public function render()
    {
        $totalOrganizations = Organization::count();
        $totalUsers = User::count();
        $totalEvents = Event::count();
        $totalGuests = Guest::count();

        $activeOrganizations = Organization::where('is_suspended', false)->count();
        $newUsers7 = User::where('created_at', '>=', now()->subDays(7))->count();
        $newUsers30 = User::where('created_at', '>=', now()->subDays(30))->count();
        $newOrgs7 = Organization::where('created_at', '>=', now()->subDays(7))->count();
        $newOrgs30 = Organization::where('created_at', '>=', now()->subDays(30))->count();
        $events30d = Event::where('created_at', '>=', now()->subDays(30))->count();

        $usersWithoutOrg = User::whereDoesntHave('organizations')->count();
        $orgsWithoutEvents = Organization::whereDoesntHave('events')->count();
        $orgsWithoutOwner = Organization::whereDoesntHave('users', fn ($q) => $q->where('organization_users.role', 'owner'))->count();
        $systemAdminsCount = User::where('is_system_admin', true)->count();
        $suspendedOrgCount = Organization::where('is_suspended', true)->count();

        $billing = app(SystemBillingService::class);
        $mrr = $billing->getMRR();
        $activeSubscriptions = $billing->getActiveSubscriptions();
        $churn = $billing->getChurnRate();

        $recentOrganizations = Organization::latest()->limit(5)->get();
        $recentUsers = User::latest()->limit(5)->get();

        return view('livewire.system.dashboard', [
            'totalOrganizations' => $totalOrganizations,
            'totalUsers' => $totalUsers,
            'totalEvents' => $totalEvents,
            'totalGuests' => $totalGuests,
            'activeOrganizations' => $activeOrganizations,
            'newUsers7' => $newUsers7,
            'newUsers30' => $newUsers30,
            'newOrgs7' => $newOrgs7,
            'newOrgs30' => $newOrgs30,
            'events30d' => $events30d,
            'usersWithoutOrg' => $usersWithoutOrg,
            'orgsWithoutEvents' => $orgsWithoutEvents,
            'orgsWithoutOwner' => $orgsWithoutOwner,
            'systemAdminsCount' => $systemAdminsCount,
            'suspendedOrgCount' => $suspendedOrgCount,
            'mrr' => $mrr,
            'activeSubscriptions' => $activeSubscriptions,
            'churn' => $churn,
            'recentOrganizations' => $recentOrganizations,
            'recentUsers' => $recentUsers,
        ]);
    }
}
