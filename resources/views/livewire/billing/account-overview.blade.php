<div>
    @if($organization === null)
        <p class="text-gray-500">{{ __('No organization selected.') }}</p>
    @elseif($account === null)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-medium text-gray-900">{{ __('Account') }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ __('No account is attached to this organization.') }}</p>
            <p class="mt-1 text-sm text-gray-500">{{ __('Organization') }}: {{ $organization->name }}</p>
            @can('update', $organization)
                <div class="mt-4">
                    <button
                        type="button"
                        wire:click="createAccount"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-brand hover:bg-brand-hover"
                    >
                        {{ __('Create account for this organization') }}
                    </button>
                </div>
            @endcan
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Account overview') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Organization') }}: {{ $organization->name }}</p>
            </div>
            <dl class="divide-y divide-gray-200">
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Account ID') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->id }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Organization account_id') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $organization->account_id ?? __('—') }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Type') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->type ?? __('—') }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->name ?? __('—') }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Owner user ID') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->owner_user_id ?? __('—') }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('SUMIT customer ID') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->sumit_customer_id ?? __('—') }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Created') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->created_at?->format('Y-m-d H:i') ?? __('—') }}</dd>
                </div>
                <div class="px-4 py-3 sm:px-6 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Updated') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $account->updated_at?->format('Y-m-d H:i') ?? __('—') }}</dd>
                </div>
            </dl>
            <div class="px-4 py-3 sm:px-6 border-t border-gray-200 flex gap-4">
                <a href="{{ route('billing.entitlements') }}" class="text-sm font-medium text-brand hover:text-indigo-900">{{ __('Entitlements') }}</a>
                <a href="{{ route('billing.usage') }}" class="text-sm font-medium text-brand hover:text-indigo-900">{{ __('Usage') }}</a>
                <a href="{{ route('billing.intents') }}" class="text-sm font-medium text-brand hover:text-indigo-900">{{ __('Billing intents') }}</a>
            </div>
        </div>
    @endif
</div>
