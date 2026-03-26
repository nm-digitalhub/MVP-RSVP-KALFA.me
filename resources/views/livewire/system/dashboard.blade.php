<div class="max-w-7xl mx-auto space-y-8" wire:poll.{{ $pollInterval }}s>
    {{-- Auto-refresh badge --}}
    <div class="flex items-center justify-between">
        <div></div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span class="inline-block size-2 rounded-full bg-green-400 animate-pulse"></span>
            {{ __('Live — refreshes every :n seconds', ['n' => $pollInterval]) }}
            &nbsp;·&nbsp;
            {{ __('Last updated') }}: <span class="font-mono text-gray-500">{{ $lastRefreshedAt }}</span>
        </div>
    </div>
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

    {{-- Real-time Monitoring --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                <span class="inline-block size-2 rounded-full bg-green-400 animate-pulse"></span>
                {{ __('System Real-time Monitoring') }}
            </h2>
        </div>

        {{-- Infrastructure Status Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-0 divide-x divide-y divide-gray-100 border-b border-gray-100">
            {{-- Database --}}
            <div class="px-4 py-3 flex items-start gap-3">
                <span @class(['mt-0.5 inline-block size-2.5 rounded-full shrink-0', 'bg-green-400' => $dbAlive, 'bg-red-500' => !$dbAlive])></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">{{ __('Database') }}</p>
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ strtoupper($dbDriver) }} <span @class(['text-xs font-normal', 'text-green-600' => $dbAlive, 'text-red-600' => !$dbAlive])>{{ $dbAlive ? __('OK') : __('ERROR') }}</span></p>
                </div>
            </div>

            {{-- Cache --}}
            <div class="px-4 py-3 flex items-start gap-3">
                <span @class(['mt-0.5 inline-block size-2.5 rounded-full shrink-0', 'bg-green-400' => $cacheAlive, 'bg-red-500' => !$cacheAlive])></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">{{ __('Cache') }}</p>
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ strtoupper($cacheDriver) }} <span @class(['text-xs font-normal', 'text-green-600' => $cacheAlive, 'text-red-600' => !$cacheAlive])>{{ $cacheAlive ? __('OK') : __('ERROR') }}</span></p>
                </div>
            </div>

            {{-- Queue --}}
            <div class="px-4 py-3 flex items-start gap-3">
                @php $queueOk = $queueFailed === 0; @endphp
                <span @class(['mt-0.5 inline-block size-2.5 rounded-full shrink-0', 'bg-green-400' => $queueOk, 'bg-yellow-400' => !$queueOk])></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">{{ __('Queue') }}</p>
                    <p class="text-sm font-semibold text-gray-800 uppercase truncate">{{ $queueDriver }}</p>
                </div>
            </div>

            {{-- Environment --}}
            <div class="px-4 py-3 flex items-start gap-3">
                <span @class(['mt-0.5 inline-block size-2.5 rounded-full shrink-0', 'bg-blue-400' => $appEnv === 'production', 'bg-amber-400' => $appEnv !== 'production'])></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-gray-500">{{ __('Environment') }}</p>
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ ucfirst($appEnv) }}</p>
                </div>
            </div>
        </div>

        {{-- Job Queue Counters --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-0 divide-x divide-gray-100 border-b border-gray-100">
            <div class="px-4 py-3">
                <p class="text-xs font-medium text-gray-500">{{ __('Pending jobs') }}</p>
                @if ($queuePending >= 0)
                    <p class="mt-0.5 text-2xl font-semibold {{ $queuePending > 50 ? 'text-amber-600' : 'text-gray-900' }}">{{ number_format($queuePending) }}</p>
                @else
                    <p class="mt-0.5 text-sm text-gray-400">{{ __('N/A') }}</p>
                @endif
            </div>

            <div class="px-4 py-3">
                <p class="text-xs font-medium text-gray-500">{{ __('Failed jobs') }}</p>
                @if ($queueFailed >= 0)
                    <p class="mt-0.5 text-2xl font-semibold {{ $queueFailed > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($queueFailed) }}</p>
                @else
                    <p class="mt-0.5 text-sm text-gray-400">{{ __('N/A') }}</p>
                @endif
            </div>

            <div class="px-4 py-3">
                <p class="text-xs font-medium text-gray-500">PHP</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 font-mono">{{ $phpVersion }}</p>
            </div>

            <div class="px-4 py-3">
                <p class="text-xs font-medium text-gray-500">Laravel</p>
                <p class="mt-0.5 text-sm font-semibold text-gray-800 font-mono">{{ $laravelVersion }}</p>
            </div>
        </div>

        {{-- Failed jobs alert --}}
        @if ($queueFailed > 0)
            <div class="px-4 py-2 bg-red-50 border-b border-red-100 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-500 shrink-0" />
                <p class="text-sm text-red-700">
                    {{ trans_choice(':count failed job|:count failed jobs', $queueFailed) }}
                    — <a href="/telescope/failed-jobs" target="_blank" class="font-medium underline hover:no-underline">{{ __('View in Telescope') }}</a>
                </p>
            </div>
        @endif

        {{-- *** CRITICAL SIGNALS *** --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-0 divide-x divide-y divide-gray-100 border-b border-gray-100">

            {{-- 1. Queue Worker Status --}}
            <div class="px-4 py-4">
                @php
                    $workerStatus = $queueWorkerStatus['status'];
                    $workerAge    = $queueWorkerStatus['oldestAgeMinutes'];
                    $workerIcon = match($workerStatus) {
                        'healthy'  => 'bg-green-400',
                        'stalled'  => 'bg-red-500 animate-pulse',
                        'idle'     => 'bg-gray-300',
                        default    => 'bg-gray-300',
                    };
                    $workerLabel = match($workerStatus) {
                        'healthy'  => __('Worker active'),
                        'stalled'  => __('Worker stalled'),
                        'idle'     => __('Worker idle'),
                        default    => __('N/A'),
                    };
                    $workerColor = match($workerStatus) {
                        'healthy'  => 'text-green-700',
                        'stalled'  => 'text-red-700',
                        default    => 'text-gray-500',
                    };
                @endphp
                <div class="flex items-start gap-3">
                    <span class="mt-1 inline-block size-2.5 rounded-full shrink-0 {{ $workerIcon }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Queue Worker') }}</p>
                        <p class="mt-1 text-sm font-semibold {{ $workerColor }}">{{ $workerLabel }}</p>
                        @if ($workerAge !== null)
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ __('Oldest job') }}: {{ $workerAge }} {{ __('min') }}
                                @if ($workerStatus === 'stalled')
                                    — <span class="text-red-600 font-medium">{{ __('check Supervisor') }}</span>
                                @endif
                            </p>
                        @elseif ($workerStatus === 'idle')
                            <p class="mt-0.5 text-xs text-gray-400">{{ __('Queue is empty') }}</p>
                        @else
                            <p class="mt-0.5 text-xs text-gray-400">{{ __('Non-database driver') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. Disk Free Space --}}
            <div class="px-4 py-4">
                @php
                    $diskColor   = $diskFreePercent < 10 ? 'bg-red-500' : ($diskFreePercent < 25 ? 'bg-amber-400' : 'bg-green-400');
                    $diskBarFill = max(2, min(100, (int) $diskFreePercent));
                @endphp
                <div class="flex items-start gap-3">
                    <span class="mt-1 inline-block size-2.5 rounded-full shrink-0 {{ $diskColor }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Disk Space') }}</p>
                        <p class="mt-1 text-sm font-semibold text-gray-800">
                            {{ $diskFreeGb }} GB {{ __('free') }}
                            <span class="text-xs font-normal text-gray-400">/ {{ $diskTotalGb }} GB</span>
                        </p>
                        {{-- progress bar --}}
                        <div class="mt-1.5 h-1.5 w-full rounded-full bg-gray-100">
                            <div class="h-1.5 rounded-full transition-all {{ $diskColor }}" style="width: {{ $diskFreePercent }}%"></div>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $diskFreePercent }}% {{ __('available') }}</p>
                    </div>
                </div>
            </div>

            {{-- 3. Recent Exceptions --}}
            <div class="px-4 py-4">
                @php
                    $excColor  = $recentExceptions > 50 ? 'bg-red-500' : ($recentExceptions > 10 ? 'bg-amber-400' : 'bg-green-400');
                    $excText   = $recentExceptions > 50 ? 'text-red-700' : ($recentExceptions > 10 ? 'text-amber-700' : 'text-gray-800');
                @endphp
                <div class="flex items-start gap-3">
                    <span class="mt-1 inline-block size-2.5 rounded-full shrink-0 {{ $excColor }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Exceptions (24h)') }}</p>
                        @if ($recentExceptions >= 0)
                            <p class="mt-1 text-2xl font-semibold {{ $excText }}">{{ number_format($recentExceptions) }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ __('via Pulse') }}
                                · <a href="/telescope/exceptions" target="_blank" class="underline hover:no-underline">{{ __('Telescope') }}</a>
                            </p>
                        @else
                            <p class="mt-1 text-sm text-gray-400">{{ __('N/A') }}</p>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- Stalled worker alert --}}
        @if ($queueWorkerStatus['status'] === 'stalled')
            <div class="px-4 py-2 bg-red-50 border-b border-red-100 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-500 shrink-0" />
                <p class="text-sm text-red-700">
                    {{ __('Queue worker appears stalled — oldest job has been waiting :min minutes. Check Supervisor or restart the worker.', ['min' => $queueWorkerStatus['oldestAgeMinutes']]) }}
                </p>
            </div>
        @endif

        {{-- Pulse + Telescope links --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4">
            <a href="/pulse" target="_blank" class="flex items-center justify-between p-3 rounded-lg border border-indigo-100 bg-indigo-50/50 hover:bg-indigo-50 transition-colors group">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-chart-bar class="w-5 h-5 text-indigo-600 shrink-0" />
                    <div>
                        <p class="font-medium text-indigo-900 text-sm">{{ __('Laravel Pulse') }}</p>
                        <p class="text-xs text-indigo-500">{{ __('Servers · Requests · Queues · Exceptions') }}</p>
                    </div>
                </div>
                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 text-indigo-400 group-hover:text-indigo-600 shrink-0" />
            </a>

            <a href="/telescope" target="_blank" class="flex items-center justify-between p-3 rounded-lg border border-amber-100 bg-amber-50/50 hover:bg-amber-50 transition-colors group">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-bug-ant class="w-5 h-5 text-amber-600 shrink-0" />
                    <div>
                        <p class="font-medium text-amber-900 text-sm">{{ __('Laravel Telescope') }}</p>
                        <p class="text-xs text-amber-500">{{ __('Requests · Queries · Jobs · Mail') }}</p>
                    </div>
                </div>
                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 text-amber-400 group-hover:text-amber-600 shrink-0" />
            </a>
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

    {{-- Billing (live from SystemBillingService / OfficeGuy) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">{{ __('Billing') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div><span class="text-gray-500">{{ __('MRR') }}:</span> <strong>{{ number_format($mrr ?? 0, 2) }}</strong></div>
            <div><span class="text-gray-500">{{ __('Active subscriptions') }}:</span> <strong>{{ $activeSubscriptionCount ?? 0 }}</strong></div>
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

    {{-- OfficeGuy integration active --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center gap-2">
            <span class="inline-block size-2 rounded-full bg-green-500"></span>
            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider">{{ __('OfficeGuy integration') }}</h2>
        </div>
        <p class="text-sm text-gray-500 mt-1">{{ __('Billing metrics are sourced live from SystemBillingService via the officeguy/laravel-sumit-gateway.') }}</p>
    </div>
</div>
