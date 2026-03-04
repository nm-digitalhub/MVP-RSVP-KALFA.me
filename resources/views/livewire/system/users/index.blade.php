<div class="max-w-7xl mx-auto space-y-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form wire:submit.prevent class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Search') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm" placeholder="{{ __('Name or email') }}" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('System admin') }}</label>
                <select wire:model.live="filter_system_admin" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                    <option value="0">{{ __('No') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('No organization') }}</label>
                <select wire:model.live="filter_no_organization" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Recent') }}</label>
                <select wire:model.live="filter_recent" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">{{ __('Any') }}</option>
                    <option value="7">{{ __('Last 7 days') }}</option>
                    <option value="30">{{ __('Last 30 days') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Disabled') }}</label>
                <select wire:model.live="filter_suspended" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                    <option value="">{{ __('Any') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                    <option value="0">{{ __('No') }}</option>
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Email') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('System Admin') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Organizations') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Created') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $u)
                        <tr wire:key="user-{{ $u->id }}">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                <a href="{{ route('system.users.show', $u) }}" class="text-indigo-600 hover:text-indigo-900">{{ $u->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $u->email }}</td>
                            <td class="px-4 py-3">
                                @if($u->is_system_admin)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">{{ __('Yes') }}</span>
                                @else
                                    <span class="text-gray-500 text-sm">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $u->organizations_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $u->created_at?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">
                                @if($u->id !== auth()->id())
                                    <button type="button" wire:click="toggleAdmin({{ $u->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        {{ $u->is_system_admin ? __('Demote') : __('Promote') }}
                                    </button>
                                @else
                                    <span class="text-gray-400 text-sm">{{ __('You') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No users.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    </div>
</div>
