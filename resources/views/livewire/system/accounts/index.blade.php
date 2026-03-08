<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex justify-end">
        <a href="{{ route('system.accounts.create') }}" class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2.5 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition-colors duration-200" aria-label="{{ __('Create account') }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('Create account') }}
        </a>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form wire:submit.prevent class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('ID') }}</label>
                <input type="number" wire:model.live.debounce.300ms="search_id" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm w-24" placeholder="ID" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Type') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search_type" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm w-32" placeholder="{{ __('organization / individual') }}" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Name') }}</label>
                <input type="text" wire:model.live.debounce.300ms="search_name" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm w-40" placeholder="{{ __('Search by name') }}" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('Owner user ID') }}</label>
                <input type="number" wire:model.live.debounce.300ms="search_owner_user_id" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm w-24" placeholder="ID" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">{{ __('SUMIT customer ID') }}</label>
                <input type="number" wire:model.live.debounce.300ms="search_sumit_customer_id" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm w-28" placeholder="ID" />
            </div>
        </form>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ID') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Owner') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('SUMIT ID') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Organizations') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($accounts as $account)
                        <tr wire:key="account-{{ $account->id }}">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $account->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->type }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->owner?->name ?? $account->owner_user_id ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->sumit_customer_id ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->organizations->count() }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('system.accounts.show', $account) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No accounts.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-2 border-t border-gray-200">
            {{ $accounts->links() }}
        </div>
    </div>
</div>
