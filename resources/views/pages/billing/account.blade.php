<x-layouts.app>
    <x-slot:title>{{ __('Billing & Entitlements') }}</x-slot:title>
    <x-slot:containerWidth>max-w-3xl</x-slot:containerWidth>
    <x-slot:header>
        <x-page-header
            :title="__('Billing & Entitlements')"
            :subtitle="__('Account overview for current organization')"
        />
    </x-slot:header>

    @session('warning')
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
            <svg class="mt-0.5 w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $value }}</span>
        </div>
    @endsession

    {{-- Query parameter-based messaging for billing redirects --}}
    @php
        $reasonMessages = [
            'no_account' => [
                'title' => 'חשבון לא מחובר',
                'message' => 'הארגון שלך לא מקושר לחשבון חיוב. אנא צור קשר עם התמיכה.',
                'icon' => 'heroicon-o-user-group',
            ],
            'no_active_plan' => [
                'title' => 'אין תוכנית פעילה',
                'message' => 'החשבון שלך אינו תוכנית פעילה. בחר תוכנית כדי להמשיך להשתמש במערכת.',
                'icon' => 'heroicon-o-credit-card',
            ],
            'trial_expired' => [
                'title' => 'תקופת הניסיון הסתיימה',
                'message' => 'תקופת הניסיון שלך הסתיימה. בחר תוכנית כדי להמשיך להשתמש במערכת.',
                'icon' => 'heroicon-o-clock',
            ],
            'subscription_expired' => [
                'title' => 'המנוי פג',
                'message' => 'המנוי שלך פג תוקף. חדש את המנוי כדי להמשיך להשתמש במערכת.',
                'icon' => 'heroicon-o-refresh',
            ],
            'subscription_pending' => [
                'title' => 'תשלום ממתין',
                'message' => 'המנוי שלך ממתין תשלום. אנא השלם את התשלום כדי להמשיך.',
                'icon' => 'heroicon-o-receipt',
            ],
        ];
        $currentReason = request()->query('reason');
        $reasonInfo = $reasonMessages[$currentReason] ?? null;
    @endphp

    @if($reasonInfo)
        <div class="mb-6 rounded-xl border border-stroke bg-card p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand/10 text-brand">
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($currentReason === 'no_account')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 016-1.5v-4.5A6 6 0 019 10.5V4.5" />
                        @elseif($currentReason === 'no_active_plan')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        @elseif($currentReason === 'trial_expired')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        @elseif($currentReason === 'subscription_expired')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        @endif
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-content">{{ $reasonInfo['title'] }}</h3>
                    <p class="mt-1 text-sm text-content-muted">{{ $reasonInfo['message'] }}</p>
                    <div class="mt-4">
                        <a href="{{ route('select-plan') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-hover focus-visible:outline focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 transition-all">
                            <span>בחר תוכנית</span>
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5M15 11l-5-5" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <livewire:billing.account-overview />
</x-layouts.app>
