<div class="max-w-7xl mx-auto space-y-8">
    {{-- KPIs --}}
    <div>
        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">{{ __('Key metrics') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('Total users') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalUsers ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('Total organizations') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalOrganizations ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('Active organizations') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $activeOrganizations ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('New users (7d / 30d)') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $newUsers7 ?? 0 }} / {{ $newUsers30 ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('New orgs (7d / 30d)') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $newOrgs7 ?? 0 }} / {{ $newOrgs30 ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('Events total') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalEvents ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('Events (30d)') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $events30d ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500">{{ __('Guests') }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $totalGuests ?? 0 }}</p>
            </div>
        </div>
    </div>

    {{-- Health --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">{{ __('Health') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
            <div><span class="text-gray-500">{{ __('Users without org') }}:</span> <strong>{{ $usersWithoutOrg ?? 0 }}</strong></div>
            <div><span class="text-gray-500">{{ __('Orgs without events') }}:</span> <strong>{{ $orgsWithoutEvents ?? 0 }}</strong></div>
            <div><span class="text-gray-500">{{ __('Orgs without owner') }}:</span> <strong>{{ $orgsWithoutOwner ?? 0 }}</strong></div>
            <div><span class="text-gray-500">{{ __('System admins') }}:</span> <strong>{{ $systemAdminsCount ?? 0 }}</strong></div>
            <div><span class="text-gray-500">{{ __('Suspended orgs') }}:</span> <strong>{{ $suspendedOrgCount ?? 0 }}</strong></div>
        </div>
    </div>

    {{-- Billing (from adapter) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">{{ __('Billing') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><span class="text-gray-500">{{ __('MRR') }}:</span> <strong>{{ number_format($mrr ?? 0, 2) }}</strong></div>
            <div><span class="text-gray-500">{{ __('Active subscriptions') }}:</span> <strong>{{ is_array($activeSubscriptions ?? []) ? count($activeSubscriptions) : 0 }}</strong></div>
            <div><span class="text-gray-500">{{ __('Churn') }}:</span> <strong>{{ number_format(($churn ?? 0) * 100, 1) }}%</strong></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Recent organizations --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Recent Organizations') }}</h2>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse(($recentOrganizations ?? []) as $org)
                    <li wire:key="org-{{ $org->id }}" class="px-4 py-3 text-sm text-gray-700">{{ $org->name }} <span class="text-gray-500">— {{ $org->created_at?->format('Y-m-d') }}</span></li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-500 text-center">{{ __('No organizations yet.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Recent users --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Recent Users') }}</h2>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse(($recentUsers ?? []) as $u)
                    <li wire:key="user-{{ $u->id }}" class="px-4 py-3 text-sm text-gray-700">{{ $u->name }} ({{ $u->email }}) <span class="text-gray-500">— {{ $u->created_at?->format('Y-m-d') }}</span></li>
                @empty
                    <li class="px-4 py-6 text-sm text-gray-500 text-center">{{ __('No users yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- OfficeGuy / integration note --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('OfficeGuy integration') }}</h2>
        <p class="text-sm text-gray-500">{{ __('Billing metrics and subscription details are provided via SystemBillingService when OfficeGuy is wired.') }}</p>
    </div>
</div>
