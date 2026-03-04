<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <a href="{{ route('system.accounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('Back to accounts') }}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ __('Account') }} #{{ $account->id }}</h1>
        <p class="text-sm text-gray-500">{{ $account->name ?? '—' }} · {{ $account->type }}</p>
    </div>

    <div class="border-b border-gray-200">
        <nav class="flex gap-4" aria-label="Tabs">
            <button type="button" wire:click="setTab('overview')" class="py-2 px-1 border-b-2 text-sm font-medium {{ $activeTab === 'overview' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('Overview') }}
            </button>
            <button type="button" wire:click="setTab('organizations')" class="py-2 px-1 border-b-2 text-sm font-medium {{ $activeTab === 'organizations' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('Organizations') }}
            </button>
            <button type="button" wire:click="setTab('entitlements')" class="py-2 px-1 border-b-2 text-sm font-medium {{ $activeTab === 'entitlements' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('Entitlements') }}
            </button>
            <button type="button" wire:click="setTab('usage')" class="py-2 px-1 border-b-2 text-sm font-medium {{ $activeTab === 'usage' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('Usage') }}
            </button>
            <button type="button" wire:click="setTab('intents')" class="py-2 px-1 border-b-2 text-sm font-medium {{ $activeTab === 'intents' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('Billing intents') }}
            </button>
        </nav>
    </div>

    @if($activeTab === 'overview')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            @if($showEditForm)
                <div class="px-4 py-4 sm:px-6 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-sm font-medium text-gray-900 mb-3">{{ __('Edit account') }}</h3>
                    <div class="space-y-3 max-w-md">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">{{ __('Name') }}</label>
                            <input type="text" wire:model="edit_name" class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
                            <x-input-error :messages="$errors->get('edit_name')" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">{{ __('Owner user ID') }}</label>
                            <input type="number" wire:model="edit_owner_user_id" class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
                            <x-input-error :messages="$errors->get('edit_owner_user_id')" class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">{{ __('SUMIT customer ID') }}</label>
                            <input type="number" wire:model="edit_sumit_customer_id" class="mt-1 block w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
                            <x-input-error :messages="$errors->get('edit_sumit_customer_id')" class="mt-1" />
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="saveAccount" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Save') }}</button>
                            <button type="button" wire:click="cancelEdit" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">{{ __('Cancel') }}</button>
                        </div>
                    </div>
                </div>
            @else
                <div class="px-4 py-3 sm:px-6 border-b border-gray-200 flex justify-end">
                    <button type="button" wire:click="openEdit" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">{{ __('Edit account') }}</button>
                </div>
            @endif
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('ID') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->id }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Type') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->type }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->name ?? '—' }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Owner') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->owner?->name ?? $account->owner_user_id ?? '—' }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('SUMIT customer ID') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->sumit_customer_id ?? '—' }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Created') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Updated') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->updated_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    @endif

    @if($activeTab === 'organizations')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Organizations attached') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Attach or detach organizations to this account. This does not change billing logic.') }}</p>
            </div>
            <div class="p-4 border-b border-gray-200 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500">{{ __('Attach organization') }}</label>
                    <select wire:model="attach_organization_id" class="mt-1 block rounded-md border border-gray-300 px-2 py-1.5 text-sm w-64">
                        <option value="">{{ __('Select organization') }}</option>
                        @foreach($organizationsAvailable as $org)
                            <option wire:key="org-{{ $org->id }}" value="{{ $org->id }}">{{ $org->name }} (ID {{ $org->id }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" wire:click="attachOrganization" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    {{ __('Attach') }}
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('ID') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($organizationsAttached as $org)
                            <tr wire:key="org-attached-{{ $org->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    <a href="{{ route('system.organizations.show', $org) }}" class="text-indigo-600 hover:text-indigo-900">{{ $org->name }}</a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $org->id }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <button type="button" wire:click="detachOrganization({{ $org->id }})" wire:confirm="{{ __('Detach this organization from the account?') }}" class="text-red-600 hover:text-red-900">
                                        {{ __('Detach') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No organizations attached.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($activeTab === 'entitlements')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Entitlements') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Feature key') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Value') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Expires at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($entitlements as $e)
                            <tr wire:key="entitlement-{{ $e->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $e->feature_key }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $e->value ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $e->expires_at?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No entitlements.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($activeTab === 'usage')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Usage') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Feature key') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Period') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Usage count') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Updated') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($usage as $u)
                            <tr wire:key="usage-{{ $u->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $u->feature_key }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $u->period_key }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $u->usage_count }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $u->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No usage records.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($activeTab === 'intents')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Billing intents') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('ID') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Intent type') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Payable') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($billingIntents as $intent)
                            <tr wire:key="intent-{{ $intent->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $intent->id }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $intent->status }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $intent->intent_type ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">
                                    @if($intent->payable_type && $intent->payable_id)
                                        {{ $intent->payable_type }} #{{ $intent->payable_id }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $intent->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No billing intents.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
