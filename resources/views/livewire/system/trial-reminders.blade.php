<x-slot name="header">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-content">Trial Reminders</h1>
            <p class="text-sm text-content-muted mt-1">Manage trial expiry reminders and send manual notifications</p>
        </div>
        <div class="flex gap-3 text-sm">
            <div class="rounded-xl bg-surface px-4 py-3 text-center">
                <div class="font-bold text-2xl">{{ $this->daysRemaining['total'] ?? 0 }}</div>
                <div class="text-xs text-content-muted">Active Trials</div>
            </div>
            <div class="rounded-xl bg-amber-50 px-4 py-3 text-center border border-amber-200">
                <div class="font-bold text-2xl text-amber-600">{{ $this->daysRemaining['expiring_soon'] ?? 0 }}</div>
                <div class="text-xs text-amber-600">≤ 7 Days</div>
            </div>
            <div class="rounded-xl bg-rose-50 px-4 py-3 text-center border border-rose-200">
                <div class="font-bold text-2xl text-rose-600">{{ $this->daysRemaining['expiring_very_soon'] ?? 0 }}</div>
                <div class="text-xs text-rose-600">≤ 3 Days</div>
            </div>
        </div>
    </div>
</x-slot>

<div class="space-y-6">
    {{-- Filters --}}
    <div class="rounded-2xl bg-card border border-stroke p-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-[200px]">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by account or owner..."
                    class="w-full rounded-xl border border-stroke bg-surface px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/10 transition"
                >
            </div>
            <div>
                <select
                    wire:model.live="statusFilter"
                    class="rounded-xl border border-stroke bg-surface px-4 py-2.5 text-sm focus:border-brand focus:ring-2 focus:ring-brand/10 transition"
                >
                    <option value="all">All Trials</option>
                    <option value="active">Active Only</option>
                    <option value="expiring_soon">Expiring Soon (≤7 days)</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            @if($search !== '' || $statusFilter !== 'all')
                <button
                    wire:click="resetFilters"
                    class="rounded-xl border border-stroke bg-surface px-4 py-2.5 text-sm font-medium hover:bg-surface-80 transition"
                >
                    Reset
                </button>
            @endif
        </div>
    </div>

    {{-- Trials Table --}}
    <div class="rounded-2xl bg-card border border-stroke overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-surface border-b border-stroke">
                    <tr>
                        <th class="px-6 py-3 font-bold text-content">Account</th>
                        <th class="px-6 py-3 font-bold text-content">Owner</th>
                        <th class="px-6 py-3 font-bold text-content">Plan</th>
                        <th class="px-6 py-3 font-bold text-content">Started</th>
                        <th class="px-6 py-3 font-bold text-content">Ends</th>
                        <th class="px-6 py-3 font-bold text-content">Days Left</th>
                        <th class="px-6 py-3 font-bold text-content text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stroke">
                    @forelse($trials as $trial)
                        <tr class="hover:bg-surface/50 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-content">{{ $trial->account->name }}</div>
                                <div class="text-xs text-content-muted">ID: {{ $trial->account->id }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-content">{{ $trial->account->owner->name }}</div>
                                <div class="text-xs text-content-muted">{{ $trial->account->owner->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-lg bg-brand/10 px-2.5 py-1 text-xs font-bold text-brand">
                                    {{ $trial->plan?->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-content-muted">
                                {{ $trial->started_at?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $daysLeft = now()->diffInDays($trial->trial_ends_at, false);
                                    $isExpiringSoon = $daysLeft <= 7;
                                    $isUrgent = $daysLeft <= 3;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="{{ $isExpiringSoon ? 'text-amber-600' : 'text-content-muted' }}">
                                        {{ $trial->trial_ends_at->format('d/m/Y') }}
                                    </span>
                                    @if($isUrgent)
                                        <span class="inline-flex items-center rounded-lg bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-600">
                                            Urgent
                                        </span>
                                    @elseif($isExpiringSoon)
                                        <span class="inline-flex items-center rounded-lg bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-600">
                                            Soon
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($daysLeft < 0)
                                    <span class="inline-flex items-center rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-600">
                                        Expired
                                    </span>
                                @elseif($daysLeft === 0)
                                    <span class="inline-flex items-center rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-600">
                                        Today
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-600">
                                        {{ $daysLeft }} days
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($daysLeft >= 0)
                                    <button
                                        wire:click="sendReminder({{ $trial->id }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-2 rounded-xl border border-stroke bg-surface px-3 py-2 text-sm font-medium hover:bg-surface-80 transition disabled:opacity-50"
                                    >
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0l7.89 5.26a2 2 0 002.22 0l7.89-5.26a2 2 0 002.22 0l7.89 5.26M16 1v4m0 4H1m0 0h15M5 23h14"/>
                                        </svg>
                                        Send Reminder
                                    </button>
                                @else
                                    <span class="inline-flex items-center text-sm text-content-muted">
                                        Expired
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-content-muted">
                                <svg class="mx-auto size-12 text-content-muted/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="font-medium">No trial subscriptions found</p>
                                <p class="text-sm mt-1">Try adjusting your filters or search terms</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($trials->hasPages())
            <div class="px-6 py-4 border-t border-stroke flex items-center justify-between">
                <div class="text-sm text-content-muted">
                    Showing {{ $trials->firstItem() }} to {{ $trials->lastItem() }} of {{ $trials->total() }} trials
                </div>
                {{ $trials->links() }}
            </div>
        @endif
    </div>

    {{-- Info Card --}}
    <div class="rounded-2xl bg-blue-50 border border-blue-200 p-6">
        <div class="flex gap-4">
            <svg class="size-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <h3 class="font-bold text-blue-900">How reminders work</h3>
                <p class="text-sm text-blue-800 mt-1">
                    Automatic reminder emails are sent daily at 9:00 AM for trials ending in 7, 3, and 1 days.
                    You can also send manual reminders using the "Send Reminder" button.
                </p>
            </div>
        </div>
    </div>
</div>
