<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <a href="{{ route('system.organizations.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('Back to organizations') }}</a>
            <div class="flex items-center gap-2 mt-1">
                <h1 class="text-2xl font-bold text-gray-900">{{ $organization->name }}</h1>
                @if($organization->is_suspended)
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __('Suspended') }}</span>
                @else
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Active') }}</span>
                @endif
            </div>
        </div>
        <form action="{{ route('system.impersonate', $organization) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Impersonate') }}</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Owner') }}</p>
            <p class="mt-1 text-gray-900">{{ $owner?->name ?? '—' }}</p>
            @if($owner)
                <p class="text-sm text-gray-500">{{ $owner->email }}</p>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Members') }}</p>
            <p class="mt-1 text-xl font-semibold text-gray-900">{{ $membersCount }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Created') }}</p>
            <p class="mt-1 text-gray-900">{{ $organization->created_at?->format('Y-m-d H:i') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-sm font-medium text-gray-500">{{ __('Last activity') }}</p>
            <p class="mt-1 text-gray-900">{{ $organization->updated_at?->format('Y-m-d H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('Billing status') }}</h2>
            <p class="text-gray-600 text-sm">{{ __('Placeholder until OfficeGuy ready.') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">{{ __('Plan / Subscription') }}</h2>
            <p class="text-gray-600 text-sm">{{ __('Plan name') }}: —</p>
            <p class="text-gray-600 text-sm mt-1">{{ __('Subscription status') }}: —</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Events') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($events as $event)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $event->name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $event->event_date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 text-sm">{{ $event->status?->value ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No events.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2 border-t border-gray-200">
            {{ $events->links() }}
        </div>
    </div>

    {{-- Admin actions --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Admin actions') }}</h2>
        </div>
        <div class="p-4 space-y-3">
            @if($organization->is_suspended)
                <button type="button" wire:click="requestAction('activate')" class="rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">{{ __('Activate organization') }}</button>
            @else
                <button type="button" wire:click="requestAction('suspend')" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">{{ __('Suspend organization') }}</button>
            @endif
            @php $members = $organization->users()->orderBy('name')->get(); @endphp
            @if($members->count() > 1)
                <div class="pt-2">
                    <p class="text-sm font-medium text-gray-700 mb-2">{{ __('Transfer ownership') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($members as $m)
                            @if($m->id !== $owner?->id)
                                <button type="button" wire:click="requestAction('transferOwnership', {{ $m->id }})" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">{{ $m->name }}</button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="pt-2 border-t border-gray-200">
                <button type="button" wire:click="requestAction('forceDelete')" class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Force delete organization') }}</button>
                <button type="button" wire:click="requestAction('resetData')" class="ml-2 rounded-md border border-red-300 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-50">{{ __('Reset data (danger zone)') }}</button>
            </div>
        </div>
    </div>

    {{-- Password confirmation --}}
    @if($pendingAction)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('Confirm password') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Enter your password to confirm this action.') }}</p>
                <div class="mt-4">
                    <input type="password" wire:model="confirmPassword" class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm px-3 py-2" placeholder="{{ __('Password') }}" autofocus />
                    @error('confirmPassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="cancelConfirm" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Cancel') }}</button>
                    <button type="button" wire:click="confirmAndExecute" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Confirm') }}</button>
                </div>
            </div>
        </div>
    @endif
</div>
