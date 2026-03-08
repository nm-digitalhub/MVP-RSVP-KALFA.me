<div class="max-w-7xl mx-auto space-y-6" role="main" aria-label="{{ __('System organizations') }}">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form wire:submit.prevent class="flex flex-wrap items-end gap-4" aria-label="{{ __('Filter organizations') }}">
            <div class="flex-1 min-w-[200px]">
                <label for="filter-name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Name') }}</label>
                <input id="filter-name" type="text" wire:model.live.debounce.300ms="search_name" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-3 py-2 text-sm rtl:text-end shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200" placeholder="{{ __('Search by name') }}" />
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="filter-owner-email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Owner email') }}</label>
                <input id="filter-owner-email" type="text" wire:model.live.debounce.300ms="search_owner_email" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-3 py-2 text-sm rtl:text-end shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200" placeholder="{{ __('Search by owner email') }}" />
            </div>
            <div class="min-w-[140px]">
                <label for="filter-suspended" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Suspended') }}</label>
                <select id="filter-suspended" wire:model.live="filter_suspended" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-3 py-2 text-sm rtl:text-end focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                    <option value="0">{{ __('No') }}</option>
                </select>
            </div>
            <div class="min-w-[140px]">
                <label for="filter-no-events" class="block text-sm font-medium text-gray-700 mb-1">{{ __('No events') }}</label>
                <select id="filter-no-events" wire:model.live="filter_no_events" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-3 py-2 text-sm rtl:text-end focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                </select>
            </div>
            <div class="min-w-[140px]">
                <label for="filter-no-users" class="block text-sm font-medium text-gray-700 mb-1">{{ __('No users') }}</label>
                <select id="filter-no-users" wire:model.live="filter_no_users" class="mt-1 block w-full min-h-[44px] rounded-lg border border-gray-300 px-3 py-2 text-sm rtl:text-end focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-0 transition-colors duration-200">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                </select>
            </div>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" aria-label="{{ __('Organizations table') }}">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200/80">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100/50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Owner') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Users') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Events') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Created') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-700 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200/80">
                    @forelse($organizations as $org)
                        <tr wire:key="org-{{ $org->id }}" class="hover:bg-gray-50/50 transition-colors duration-150 ease-out">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                <a href="{{ route('system.organizations.show', $org) }}" class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-200">{{ $org->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $org->owner()?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $org->users_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $org->events_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $org->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                @if($org->is_suspended ?? false)
                                    <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-red-100/90 border border-red-200 text-red-800">{{ __('Suspended') }}</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-green-100/90 border border-green-200 text-green-800">{{ __('Active') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <form action="{{ route('system.impersonate', $org) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] px-4 py-2.5 rounded-lg text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200" aria-label="{{ __('Impersonate organization', ['name' => $org->name]) }}">{{ __('Impersonate') }}</button>
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
        <div class="px-4 py-3 border-t border-gray-200/80">
            {{ $organizations->links() }}
        </div>
    </div>
</div>
