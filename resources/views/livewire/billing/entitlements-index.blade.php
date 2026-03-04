<div>
    @if($account === null)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600">{{ __('No account attached to this organization.') }}</p>
            <a href="{{ route('billing.account') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-900">{{ __('Go to Billing & Account') }}</a>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Entitlements') }}</h2>
                @if(!$showForm)
                    <x-primary-button type="button" wire:click="openCreate">
                        {{ __('Add entitlement') }}
                    </x-primary-button>
                @endif
            </div>

            @if($showForm)
                <div class="px-4 py-4 sm:px-6 border-b border-gray-200 bg-gray-50">
                    <div class="space-y-3 max-w-md">
                        <div>
                            <x-input-label for="entitlement_feature_key" :value="__('Feature key')" />
                            <x-text-input id="entitlement_feature_key" wire:model="feature_key" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('feature_key')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="entitlement_value" :value="__('Value')" />
                            <x-text-input id="entitlement_value" wire:model="value" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('value')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="entitlement_expires_at" :value="__('Expires at (date)')" />
                            <x-text-input id="entitlement_expires_at" type="date" wire:model="expires_at" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('expires_at')" class="mt-1" />
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button wire:click="save">{{ __('Save') }}</x-primary-button>
                            <x-secondary-button wire:click="cancelForm">{{ __('Cancel') }}</x-secondary-button>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Feature key') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Value') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Source') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Expires at') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($entitlements as $e)
                            <tr wire:key="entitlement-{{ $e->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $e->feature_key }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $e->value ?? __('—') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $e->product_entitlement_id ? __('Product') : __('Manual') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $e->expires_at?->format('Y-m-d') ?? __('—') }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <button type="button" wire:click="openEdit({{ $e->id }})" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</button>
                                    <button type="button" wire:click="deleteEntitlement({{ $e->id }})" wire:confirm="{{ __('Delete this entitlement?') }}" class="text-red-600 hover:text-red-900 mr-2">{{ __('Delete') }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No entitlements yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
