<div>
    @if($account === null)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600">{{ __('No account attached to this organization.') }}</p>
            <a href="{{ route('billing.account') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-900">{{ __('Go to Billing & Account') }}</a>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Usage') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Read-only.') }}</p>
            </div>
            <div class="px-4 py-3 border-b border-gray-200 flex gap-4 flex-wrap">
                <div>
                    <x-input-label for="filter_feature_key" :value="__('Filter by feature key')" />
                    <x-text-input id="filter_feature_key" wire:model.live.debounce.300ms="filter_feature_key" class="mt-1 block w-48" />
                </div>
                <div>
                    <x-input-label for="filter_period_key" :value="__('Filter by period')" />
                    <x-text-input id="filter_period_key" wire:model.live.debounce.300ms="filter_period_key" class="mt-1 block w-32" />
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Feature key') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Period') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Usage count') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Updated') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($usage as $u)
                            <tr wire:key="usage-{{ $u->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $u->feature_key }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $u->period_key }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $u->usage_count }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $u->updated_at?->format('Y-m-d H:i') ?? __('—') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No usage records.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
