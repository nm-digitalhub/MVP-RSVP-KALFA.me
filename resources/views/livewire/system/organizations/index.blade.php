<div class="max-w-7xl mx-auto space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form wire:submit.prevent class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Name') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search_name" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="{{ __('Search by name') }}" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Owner email') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search_owner_email" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="{{ __('Search by owner email') }}" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Suspended') }}</label>
                <select wire:model.live="filter_suspended" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                    <option value="0">{{ __('No') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('No events') }}</label>
                <select wire:model.live="filter_no_events" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('No users') }}</label>
                <select wire:model.live="filter_no_users" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                </select>
            </div>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Owner') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Users') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Events') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Created') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($organizations as $org)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                <a href="{{ route('system.organizations.show', $org) }}" class="text-indigo-600 hover:text-indigo-900">{{ $org->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $org->owner()?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $org->users_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $org->events_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $org->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                @if($org->is_suspended ?? false)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">{{ __('Suspended') }}</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ __('Active') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <form action="{{ route('system.impersonate', $org) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">{{ __('Impersonate') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No organizations.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $organizations->links() }}
        </div>
    </div>
</div>
