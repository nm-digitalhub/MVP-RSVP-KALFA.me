@php
    $remoteApiBaseUrl = (string) config('mobile.api.base_url', 'https://kalfa.me');
    $remoteApiEndpoints = config('mobile.api.endpoints', []);

    $stateConfig = [
        'initial' => 'unauthenticated',
        'api' => [
            'base_url' => $remoteApiBaseUrl,
            'login_url' => $remoteApiBaseUrl.($remoteApiEndpoints['login'] ?? '/api/mobile/auth/login'),
            'logout_url' => $remoteApiBaseUrl.($remoteApiEndpoints['logout'] ?? '/api/mobile/auth/logout'),
            'logout_others_url' => $remoteApiBaseUrl.($remoteApiEndpoints['logout_others'] ?? '/api/mobile/auth/logout/others'),
            'bootstrap_url' => $remoteApiBaseUrl.($remoteApiEndpoints['bootstrap'] ?? '/api/bootstrap'),
        ],
        'session' => [
            'status_url' => route('mobile.session.status', [], false),
            'store_url' => route('mobile.session.store', [], false),
            'destroy_url' => route('mobile.session.destroy', [], false),
        ],
        'states' => [
            'unauthenticated' => [
                'label' => 'Unauthenticated',
                'eyebrow' => 'Login required',
                'title' => 'ה־client עדיין לא מחזיק mobile token.',
                'description' => 'המצב ההתחלתי של shell נקי. מכאן היישום יוכל להחליט אם לפתוח login flow או להמשיך לשחזור session מאובטח.',
                'hints' => [
                    'אין תלות ב־web session.',
                    'אין bootstrap call לפני הכרעת auth.',
                ],
            ],
            'authenticated' => [
                'label' => 'Authenticated',
                'eyebrow' => 'Token present',
                'title' => 'יש credential למובייל וה־shell מוכן למעבר ל־bootstrap.',
                'description' => 'המצב הזה מייצג לקוח שכבר עבר login או שחזר token קיים, אבל טרם סיים רענון נתונים למסך הראשון.',
                'hints' => [
                    'יכול להתקדם ל־GET /api/bootstrap.',
                    'עדיין בלי sync או writes.',
                ],
            ],
            'syncing' => [
                'label' => 'Syncing',
                'eyebrow' => 'Bootstrap in progress',
                'title' => 'ה־client מושך bootstrap ומרענן את read cache הראשוני.',
                'description' => 'בשלב הזה ה־shell נשאר על loading state ברור, בלי redirect loops ובלי mutation semantics.',
                'hints' => [
                    'cache-first עם background refresh.',
                    'אין merge logic בשלב הזה.',
                ],
            ],
            'offline-stale' => [
                'label' => 'Offline Stale',
                'eyebrow' => 'Cached fallback',
                'title' => 'יש cache קיים, אבל הוא stale או שהרשת לא זמינה.',
                'description' => 'היישום ממשיך להציג נתונים קיימים, מסמן stale, ומנסה refresh כשאפשר לפי policy של Phase 2.',
                'hints' => [
                    'serve stale on failure.',
                    'background refresh when stale.',
                ],
            ],
            'revoked' => [
                'label' => 'Revoked',
                'eyebrow' => 'Re-auth required',
                'title' => 'ה־token נדחה או בוטל, ונדרש מעבר חד ל־re-auth.',
                'description' => 'זהו מצב terminal ל־session הנוכחי: ה־shell מציג הסבר ברור ועוצר שימוש ב־bootstrap הישן עד להתחברות מחדש.',
                'hints' => [
                    'אין silent retry אינסופי.',
                    'המשתמש חוזר למסלול login.',
                ],
            ],
        ],
    ];
@endphp

<x-layouts.mobile-shell>
    <x-slot:title>Kalfa Mobile</x-slot:title>

    <section
        x-data="mobileShellStateModel()"
        data-mobile-shell
        class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-5xl items-center justify-center"
    >
        <div class="w-full rounded-[2rem] border border-white/60 bg-white/85 p-8 shadow-[0_30px_90px_-45px_rgba(15,23,42,0.45)] backdrop-blur xl:p-10">
            <div class="flex flex-col gap-8">
                <div class="flex items-center justify-between gap-4">
                    <x-kalfa-wordmark class="justify-start" />
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-600">
                        Mobile Shell
                    </span>
                </div>

                <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
                    <div class="space-y-4">
                        <div class="inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-950 shadow-lg shadow-slate-950/20">
                            <x-kalfa-app-icon class="h-9 w-9" />
                        </div>

                        <div class="space-y-3">
                            <p class="text-sm font-medium uppercase tracking-[0.28em] text-slate-500">NativePHP Entry</p>
                            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                                נקודת כניסה יציבה ל־Kalfa Mobile
                            </h1>
                            <p class="max-w-xl text-base leading-7 text-slate-600">
                                המסך הזה משמש כ־entry route יחיד ל־NativePHP. הוא נשאר נקי מ־redirects דפדפניים,
                                ומחזיק עכשיו state model מפורש ל־auth/bootstrap lifecycle של המובייל.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-5 rounded-3xl border border-slate-200 bg-slate-50/90 p-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">State Map</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                ה־shell מחזיק חמשת מצבי העבודה של Phase 2. כרגע זו שכבת UI/state בלבד, בלי token storage
                                ובלי bootstrap side effects.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <template x-for="(definition, key) in states" :key="key">
                                <button
                                    type="button"
                                    class="flex items-center justify-between rounded-2xl border px-4 py-3 text-start transition"
                                    :class="state === key
                                        ? 'border-slate-900 bg-slate-950 text-white shadow-lg shadow-slate-950/10'
                                        : 'border-slate-200 bg-white/80 text-slate-700 hover:border-slate-300'"
                                    @click="setState(key)"
                                >
                                    <span>
                                        <span class="block text-sm font-semibold" x-text="definition.label"></span>
                                        <span class="mt-1 block text-xs uppercase tracking-[0.24em]" x-text="definition.eyebrow"></span>
                                    </span>
                                    <span class="rounded-full border border-current/15 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]">
                                        Preview
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="rounded-3xl border border-slate-200 bg-white/80 p-6 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Current State</p>

                        <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500" x-text="activeState.eyebrow"></p>
                            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950" x-text="activeState.title"></h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600" x-text="activeState.description"></p>

                            <ul class="mt-5 space-y-2 text-sm text-slate-700">
                                <template x-for="hint in activeState.hints" :key="hint">
                                    <li class="flex items-start gap-3">
                                        <span class="mt-1.5 h-2 w-2 rounded-full bg-slate-900"></span>
                                        <span x-text="hint"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50/90 p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Boot Contract</p>

                        <dl class="mt-4 space-y-3 text-sm text-slate-600">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="font-medium text-slate-500">Initial state</dt>
                                <dd class="font-mono text-slate-900">unauthenticated</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="font-medium text-slate-500">Start URL</dt>
                                <dd class="font-mono text-slate-900">/mobile</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="font-medium text-slate-500">Login API</dt>
                                <dd class="font-mono text-slate-900" x-text="api.login_url"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="font-medium text-slate-500">Bootstrap API</dt>
                                <dd class="font-mono text-slate-900" x-text="api.bootstrap_url"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="font-medium text-slate-500">Local session</dt>
                                <dd class="font-mono text-slate-900" x-text="session.status_url"></dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="font-medium text-slate-500">Offline mode</dt>
                                <dd class="font-mono text-slate-900">read-cache only</dd>
                            </div>
                        </dl>

                        <div class="mt-5 rounded-2xl border border-slate-200 bg-white/80 px-4 py-4 text-sm text-slate-700">
                            <div class="flex items-center justify-between gap-4">
                                <span class="font-medium text-slate-500">Secure storage</span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]"
                                    :class="!storage.checked
                                        ? 'bg-slate-100 text-slate-600'
                                        : storage.available
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-amber-100 text-amber-700'"
                                    x-text="!storage.checked
                                        ? 'Checking'
                                        : storage.available
                                            ? 'Available'
                                            : 'Unavailable'"
                                ></span>
                            </div>
                            <p class="mt-2 leading-6 text-slate-600">
                                <span x-show="storage.checked && storage.hasToken">נמצא token שמור, וה־shell יכול לעבור ל־authenticated state בלי להשתמש ב־localStorage.</span>
                                <span x-show="storage.checked && !storage.hasToken">אין token שמור כרגע. ה־shell נשאר על unauthenticated עד login flow.</span>
                                <span x-show="!storage.checked">ה־shell בודק אם NativePHP SecureStorage זמין והאם קיים token קודם.</span>
                            </p>
                        </div>

                        <div class="mt-6 rounded-2xl border border-slate-200 bg-white/80 px-4 py-4 text-sm leading-6 text-slate-600">
                            המצבים כאן הם source of truth ל־`S2B-1`: `unauthenticated`, `authenticated`, `syncing`,
                            `offline-stale`, `revoked`.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script id="mobile-shell-state-config" type="application/json">@json($stateConfig)</script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mobileShellStateModel', () => ({
                state: 'unauthenticated',
                states: {},

                init() {
                    const configElement = document.getElementById('mobile-shell-state-config');

                    if (! configElement) {
                        return;
                    }

                    const config = JSON.parse(configElement.textContent);

                    this.state = config.initial;
                    this.api = config.api;
                    this.states = config.states;
                    this.session = config.session;

                    this.hydrateSecureTokenState();
                },

                setState(nextState) {
                    if (! this.states[nextState]) {
                        return;
                    }

                    this.state = nextState;
                },

                async hydrateSecureTokenState() {
                    if (! window.fetch || ! this.session?.status_url) {
                        return;
                    }

                    try {
                        const response = await window.fetch(this.session.status_url, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const payload = await response.json();

                        this.storage.available = Boolean(payload.available);
                        this.storage.hasToken = Boolean(payload.has_token);
                        this.storage.checked = true;

                        this.state = payload.has_token ? 'authenticated' : 'unauthenticated';
                    } catch (error) {
                        this.storage.available = false;
                        this.storage.hasToken = false;
                        this.storage.checked = true;
                        this.state = 'unauthenticated';
                    }
                },

                get activeState() {
                    return this.states[this.state] ?? {
                        eyebrow: 'Unknown',
                        title: 'Unknown mobile shell state.',
                        description: 'The state model has not been initialized yet.',
                        hints: [],
                    };
                },

                storage: {
                    available: false,
                    hasToken: false,
                    checked: false,
                },

                api: {},
                session: {},
            }));
        });
    </script>
</x-layouts.mobile-shell>
