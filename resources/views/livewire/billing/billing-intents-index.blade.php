<div>
    @if($account === null)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600">{{ __('No account attached to this organization.') }}</p>
            <a href="{{ route('billing.account') }}" class="mt-4 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-900">{{ __('Go to Billing & Account') }}</a>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">{{ __('Billing intents') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Read-only.') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('ID') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Intent type') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Payable') }}</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Created') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($intents as $intent)
                            <tr wire:key="intent-{{ $intent->id }}">
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $intent->id }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $intent->status }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $intent->intent_type ?? __('—') }}</td>
                                <td class="px-4 py-2 text-sm text-gray-600">
                                    @if($intent->payable_type && $intent->payable_id)
                                        {{ $intent->payable_type }} #{{ $intent->payable_id }}
                                    @else
                                        {{ __('—') }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-600">{{ $intent->created_at?->format('Y-m-d H:i') ?? __('—') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No billing intents.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
