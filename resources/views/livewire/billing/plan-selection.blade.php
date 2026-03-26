<div class="max-w-5xl mx-auto px-4 py-12 space-y-10 animate-in fade-in duration-700">

    {{-- Header --}}
    <div class="text-center space-y-3">
        <x-auth-logo />
        <h1 class="text-4xl font-black text-content tracking-tighter">{{ __('Choose a Plan') }}</h1>
        <p class="text-lg text-content-muted font-medium">
            {{ __('Start with a free 14-day trial — no credit card required.') }}
        </p>
        @if($organization)
            <p class="text-sm text-content-muted">
                {{ __('Organization') }}: <span class="font-bold text-content">{{ $organization->name }}</span>
            </p>
        @endif
    </div>

    {{-- Flash --}}
    @session('success')
        <div class="flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
            <x-heroicon-o-check-circle class="size-5 shrink-0 mt-0.5 text-green-600" />
            <span>{{ $value }}</span>
        </div>
    @endsession

    {{-- Plan Cards --}}
    @if($plans->isEmpty())
        <div class="rounded-2xl border border-stroke bg-card p-12 text-center text-content-muted">
            <x-kalfa-app-icon class="mx-auto mb-4 h-12 w-12 opacity-80" alt="" />
            {{ __('No plans available at the moment. Please contact support.') }}
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($plans as $plan)
                @php
                    $monthlyPrice = $plan->activePrices->firstWhere('billing_cycle', 'monthly');
                    $priceDisplay = $monthlyPrice
                        ? '₪' . number_format($monthlyPrice->amount / 100, 0) . ' / ' . __('month')
                        : __('Contact us');
                    $isPopular = $plan->slug === 'growth';
                @endphp

                <div wire:key="plan-{{ $plan->id }}" class="relative flex flex-col rounded-3xl border {{ $isPopular ? 'border-brand shadow-brand bg-card' : 'border-stroke bg-card' }} p-8 shadow-xl shadow-slate-900/5 transition-all hover:shadow-2xl hover:-translate-y-1 duration-300">

                    @if($isPopular)
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-1 text-[10px] font-black uppercase tracking-widest text-white shadow-lg shadow-brand/30">
                                <x-heroicon-m-star class="size-3" />
                                {{ __('Most Popular') }}
                            </span>
                        </div>
                    @endif

                    {{-- Plan name & product --}}
                    <div class="space-y-1 mb-6">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-content-muted">
                            {{ $plan->product?->name }}
                        </p>
                        <h2 class="text-2xl font-black text-content tracking-tight">{{ $plan->name }}</h2>
                        @if($plan->description)
                            <p class="text-sm text-content-muted leading-relaxed">{{ $plan->description }}</p>
                        @endif
                    </div>

                    {{-- Price --}}
                    <div class="mb-8">
                        <span class="text-4xl font-black text-content tracking-tighter">{{ $priceDisplay }}</span>
                    </div>

                    {{-- Actions --}}
                    <div class="mt-auto space-y-3">
                        {{-- Trial CTA --}}
                        <button
                            wire:click="confirmTrial({{ $plan->id }})"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl {{ $isPopular ? 'bg-brand text-white shadow-lg shadow-brand/25 hover:bg-brand-hover' : 'bg-surface border border-stroke text-content hover:bg-brand hover:text-white hover:border-brand' }} px-6 py-3.5 text-sm font-black transition-all active:scale-95 disabled:opacity-50"
                        >
                            <x-heroicon-o-play-circle class="size-4" />
                            {{ __('Start 14-day Trial') }}
                        </button>

                        {{-- Purchase CTA --}}
                        <button
                            wire:click="purchase({{ $plan->id }})"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-2xl border border-stroke bg-transparent text-content-muted hover:text-content hover:border-brand px-6 py-3.5 text-sm font-black transition-all active:scale-95 disabled:opacity-50"
                        >
                            <x-heroicon-o-credit-card class="size-4" />
                            {{ __('Purchase') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Back to billing --}}
    <div class="text-center">
        <a href="{{ route('billing.account') }}" class="text-sm text-content-muted hover:text-brand transition-colors font-medium">
            ← {{ __('Back to Billing') }}
        </a>
    </div>

    {{-- Trial Confirmation Modal --}}
    @if($showConfirmTrial && $selectedPlanId)
        @php $selectedPlan = $plans->firstWhere('id', $selectedPlanId) @endphp
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-in fade-in duration-200"
            wire:click.self="cancelConfirm"
        >
            <div class="w-full max-w-md rounded-3xl bg-card border border-stroke shadow-2xl p-8 space-y-6 animate-in zoom-in-95 duration-200">
                <div class="space-y-2">
                    <h3 class="text-xl font-black text-content">{{ __('Start Free Trial') }}</h3>
                    <p class="text-sm text-content-muted">
                        {{ __('You\'re about to start a 14-day free trial of the :plan plan. No credit card required.', ['plan' => $selectedPlan?->name]) }}
                    </p>
                </div>

                <div class="rounded-2xl bg-green-50 border border-green-200 px-5 py-4 text-sm text-green-800 space-y-1">
                    <p class="font-black">{{ __('Included in your trial:') }}</p>
                    <p>{{ __('Full access to all features in the :plan plan for 14 days.', ['plan' => $selectedPlan?->name]) }}</p>
                    <p>{{ __('Trial ends :date.', ['date' => now()->addDays(14)->format('d.m.Y')]) }}</p>
                </div>

                <div class="flex gap-3">
                    <button
                        wire:click="startTrial"
                        wire:loading.attr="disabled"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-2xl bg-brand text-white font-black px-6 py-3.5 shadow-lg shadow-brand/25 hover:bg-brand-hover transition-all active:scale-95 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="startTrial">{{ __('Confirm Trial') }}</span>
                        <span wire:loading wire:target="startTrial" class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            {{ __('Starting...') }}
                        </span>
                    </button>
                    <button
                        wire:click="cancelConfirm"
                        class="flex-1 inline-flex items-center justify-center rounded-2xl border border-stroke text-content-muted font-black px-6 py-3.5 hover:bg-surface transition-all active:scale-95"
                    >
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
