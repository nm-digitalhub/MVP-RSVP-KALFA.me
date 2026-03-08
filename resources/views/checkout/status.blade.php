<x-layouts.app>
    <x-slot:title>{{ __('Payment Status') }}</x-slot:title>

<div class="min-h-screen bg-gray-50 py-12 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg p-8">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Payment Status') }}</h1>
        <p class="mt-2 text-gray-600">
            {{ __('Status') }}: <strong>{{ $payment->status->value }}</strong>
        </p>
        @if($payment->status->value === 'succeeded')
            <p class="mt-2 text-green-600">{{ __('Payment completed successfully.') }}</p>
        @elseif($payment->status->value === 'failed')
            <p class="mt-2 text-red-600">{{ __('Payment failed.') }}</p>
        @else
            <p class="mt-2 text-amber-600">{{ __('Payment is being processed.') }}</p>
        @endif
    </div>
</div>
</x-layouts.app>
