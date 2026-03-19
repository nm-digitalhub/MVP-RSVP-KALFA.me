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
            'access_token_key' => (string) config('mobile.secure_storage.access_token_key', 'kalfa.mobile.access_token'),
        ],
        'shell' => [
            'default_device_name' => trim(sprintf('%s Mobile', (string) config('app.name', 'Kalfa'))),
        ],
        'states' => [
            'unauthenticated' => [
                'label' => 'Unauthenticated',
                'eyebrow' => 'Login required',
                'title' => 'אין remote session מאושר ל־mobile client.',
                'description' => 'ה־shell יכול להחזיק credential מקומי, אבל הוא לא עובר ל־authenticated עד שהשרת מאשר bootstrap תקין.',
                'hints' => [
                    'Remote login מול השרת בלבד.',
                    '422 נשאר unauthenticated.',
                ],
            ],
            'authenticated' => [
                'label' => 'Authenticated',
                'eyebrow' => 'Bootstrap approved',
                'title' => 'השרת אישר את ה־credential והחזיר bootstrap תקף.',
                'description' => 'זהו המצב היחיד שבו ה־shell מכריז authenticated: רק אחרי login או restore שהסתיימו עם bootstrap מוצלח מהשרת.',
                'hints' => [
                    'Credential מקומי בלבד לא מספיק.',
                    'ה־server נשאר source of truth.',
                ],
            ],
            'syncing' => [
                'label' => 'Syncing',
                'eyebrow' => 'Remote auth in progress',
                'title' => 'ה־client מבצע login או bootstrap מול השרת.',
                'description' => 'בשלב הזה ה־shell מושך או מאמת remote context, בלי writes עסקיים ובלי sync semantics מעבר ל־bootstrap.',
                'hints' => [
                    'Login -> SecureStorage -> Bootstrap.',
                    'אין implicit-origin לקריאות auth/data.',
                ],
            ],
            'offline-stale' => [
                'label' => 'Offline Stale',
                'eyebrow' => 'Cached fallback',
                'title' => 'המצב נשמר כחלק מה־state model, אבל לא מורחב בטיקט הזה.',
                'description' => 'S2C-2 לא פותח sync/offline semantics. הוא שומר את ה־contract בלבד כדי לא לערבב bootstrap auth עם cache policies עתידיות.',
                'hints' => [
                    'No new offline behavior in S2C-2.',
                    'State reserved for later tracks.',
                ],
            ],
            'revoked' => [
                'label' => 'Revoked',
                'eyebrow' => 'Credential rejected',
                'title' => 'השרת דחה את ה־credential, וה־shell מחק אותו מקומית.',
                'description' => '401 או 403 ב־login/bootstrap מעבירים ל־revoked, מוחקים את ה־credential דרך SecureStorage, ומחזירים את המשתמש ל־re-auth.',
                'hints' => [
                    '401/403 => delete credential.',
                    'נדרש login חדש.',
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
                                המסך הזה נשאר entry route מבודד של NativePHP, אבל עכשיו מחובר לזרימה המינימלית של
                                remote login, שמירת credential ב־SecureStorage, ו־bootstrap שמכריע אם ה־client באמת authenticated.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-5 rounded-3xl border border-slate-200 bg-slate-50/90 p-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">State Map</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                ה־shell שומר על separation קשיח בין credential מקומי לבין auth state שמאושר על ידי השרת.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <template x-for="(definition, key) in states" :key="key">
                                <div
                                    class="flex items-center justify-between rounded-2xl border px-4 py-3 text-start transition"
                                    :class="state === key
                                        ? 'border-slate-900 bg-slate-950 text-white shadow-lg shadow-slate-950/10'
                                        : 'border-slate-200 bg-white/80 text-slate-700'"
                                >
                                    <span>
                                        <span class="block text-sm font-semibold" x-text="definition.label"></span>
                                        <span class="mt-1 block text-xs uppercase tracking-[0.24em]" x-text="definition.eyebrow"></span>
                                    </span>
                                    <span class="rounded-full border border-current/15 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]">
                                        Active
                                    </span>
                                </div>
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
                                <dt class="font-medium text-slate-500">SecureStorage key</dt>
                                <dd class="font-mono text-slate-900" x-text="session.access_token_key"></dd>
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
                                <span x-show="storage.checked && storage.hasToken">נמצא credential שמור. ה־shell ינסה bootstrap מול השרת לפני שיעבור ל־authenticated.</span>
                                <span x-show="storage.checked && !storage.hasToken">אין credential שמור כרגע. ה־shell נשאר unauthenticated עד login מרוחק.</span>
                                <span x-show="!storage.checked">ה־shell בודק אם NativePHP SecureStorage זמין והאם קיים credential קודם.</span>
                            </p>
                        </div>

                        <div
                            x-show="feedback.error"
                            x-cloak
                            class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm leading-6 text-rose-700"
                            x-text="feedback.error"
                        ></div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="rounded-3xl border border-slate-200 bg-white/80 p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Remote Auth Flow</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    login מרוחק, שמירה מקומית ב־SecureStorage, ואז bootstrap עם Bearer token. רק אחרי bootstrap מוצלח
                                    ה־shell עובר ל־authenticated.
                                </p>
                            </div>

                            <span
                                class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]"
                                :class="feedback.loading ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'"
                                x-text="feedback.loading ? stageLabel : 'Idle'"
                            ></span>
                        </div>

                        <form class="mt-6 grid gap-4" @submit.prevent="submitLogin">
                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-700">Email</span>
                                <input
                                    x-model="auth.email"
                                    type="email"
                                    autocomplete="email"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                                    placeholder="name@kalfa.me"
                                >
                                <span class="text-xs text-rose-600" x-text="validationMessage('email')"></span>
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-700">Password</span>
                                <input
                                    x-model="auth.password"
                                    type="password"
                                    autocomplete="current-password"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                                    placeholder="••••••••"
                                >
                                <span class="text-xs text-rose-600" x-text="validationMessage('password')"></span>
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-medium text-slate-700">Device name</span>
                                <input
                                    x-model="auth.device_name"
                                    type="text"
                                    autocomplete="off"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                                >
                                <span class="text-xs text-rose-600" x-text="validationMessage('device_name')"></span>
                            </label>

                            <div class="flex flex-wrap gap-3 pt-2">
                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="feedback.loading"
                                >
                                    <span x-text="feedback.loading ? 'Working...' : 'Login + Bootstrap'"></span>
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="feedback.loading || !storage.hasToken"
                                    @click="restoreStoredSession()"
                                >
                                    שחזר session שמור
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="feedback.loading || !storage.hasToken"
                                    @click="clearStoredCredential()"
                                >
                                    נקה credential מקומי
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-slate-50/90 p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Bootstrap Snapshot</p>

                        <template x-if="bootstrap">
                            <div class="mt-4 space-y-4 rounded-3xl border border-slate-200 bg-white/80 px-5 py-5 text-sm text-slate-700">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-medium text-slate-500">Server status</span>
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-700">
                                        Authenticated
                                    </span>
                                </div>

                                <dl class="space-y-3">
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-medium text-slate-500">User</dt>
                                        <dd class="text-slate-900" x-text="bootstrap?.user?.email ?? 'n/a'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-medium text-slate-500">Organization</dt>
                                        <dd class="text-slate-900" x-text="bootstrap?.current_organization?.name ?? 'No current organization'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-medium text-slate-500">Abilities</dt>
                                        <dd class="text-slate-900" x-text="Array.isArray(bootstrap?.abilities) ? bootstrap.abilities.join(', ') : 'n/a'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="font-medium text-slate-500">Server time</dt>
                                        <dd class="font-mono text-slate-900" x-text="bootstrap?.server_time ?? 'n/a'"></dd>
                                    </div>
                                </dl>
                            </div>
                        </template>

                        <template x-if="! bootstrap">
                            <div class="mt-4 rounded-3xl border border-dashed border-slate-300 bg-white/70 px-5 py-5 text-sm leading-6 text-slate-600">
                                עדיין לא התקבל bootstrap מהשרת. ה־client נשאר unauthenticated עד ש־remote login או restore יסיימו
                                bootstrap תקין.
                            </div>
                        </template>
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
                api: {},
                session: {},
                shell: {},
                bootstrap: null,

                auth: {
                    email: '',
                    password: '',
                    device_name: '',
                },

                feedback: {
                    loading: false,
                    stage: null,
                    error: null,
                    validation: {},
                },

                storage: {
                    available: false,
                    hasToken: false,
                    checked: false,
                },

                async init() {
                    const configElement = document.getElementById('mobile-shell-state-config');

                    if (! configElement) {
                        return;
                    }

                    const config = JSON.parse(configElement.textContent);

                    this.state = config.initial;
                    this.api = config.api ?? {};
                    this.states = config.states ?? {};
                    this.session = config.session ?? {};
                    this.shell = config.shell ?? {};
                    this.auth.device_name = this.shell.default_device_name ?? 'Kalfa Mobile';

                    await this.hydrateSecureTokenState();
                },

                async hydrateSecureTokenState() {
                    if (! window.fetch || ! this.session?.status_url) {
                        this.storage.checked = true;
                        return;
                    }

                    try {
                        const response = await window.fetch(this.session.status_url, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const payload = await this.parseJson(response);

                        if (! response.ok) {
                            throw new Error('Unable to inspect secure storage status.');
                        }

                        this.storage.available = Boolean(payload.available);
                        this.storage.hasToken = Boolean(payload.has_token);
                        this.storage.checked = true;
                        this.state = 'unauthenticated';

                        if (this.storage.available && this.storage.hasToken) {
                            await this.restoreStoredSession(true);
                        }
                    } catch (error) {
                        this.storage.available = false;
                        this.storage.hasToken = false;
                        this.storage.checked = true;
                        this.state = 'unauthenticated';
                    }
                },

                async submitLogin() {
                    if (this.feedback.loading || ! window.fetch) {
                        return;
                    }

                    this.feedback.error = null;
                    this.feedback.validation = {};
                    this.bootstrap = null;
                    this.state = 'syncing';
                    this.feedback.loading = true;
                    this.feedback.stage = 'login';

                    try {
                        const response = await window.fetch(this.api.login_url, {
                            method: 'POST',
                            headers: this.remoteJsonHeaders(),
                            body: JSON.stringify({
                                email: this.auth.email,
                                password: this.auth.password,
                                device_name: this.auth.device_name || (this.shell.default_device_name ?? 'Kalfa Mobile'),
                            }),
                        });

                        const payload = await this.parseJson(response);

                        if (response.status === 422) {
                            this.feedback.validation = payload.errors ?? {};
                            this.feedback.error = this.extractMessage(payload, 'ההתחברות נכשלה. בדוק את פרטי ההתחברות ונסה שוב.');
                            this.state = 'unauthenticated';

                            return;
                        }

                        if (response.status === 401 || response.status === 403) {
                            await this.handleRevokedState(this.extractMessage(payload, 'השרת דחה את ה־credential.'));

                            return;
                        }

                        if (! response.ok) {
                            this.feedback.error = this.extractMessage(payload, 'לא ניתן היה להשלים login מול השרת.');
                            this.state = 'unauthenticated';

                            return;
                        }

                        if (typeof payload.access_token !== 'string' || payload.access_token.length === 0) {
                            this.feedback.error = 'השרת לא החזיר access token תקין.';
                            this.state = 'unauthenticated';

                            return;
                        }

                        const persisted = await this.persistSecureCredential(payload.access_token);

                        if (! persisted) {
                            this.feedback.error = 'לא ניתן היה לשמור את ה־credential ב־SecureStorage.';
                            this.state = 'unauthenticated';

                            return;
                        }

                        await this.bootstrapWithToken(payload.access_token);
                    } catch (error) {
                        this.feedback.error = 'לא ניתן היה להשלים login מול השרת.';
                        this.state = 'unauthenticated';
                    } finally {
                        if (this.feedback.stage === 'login') {
                            this.feedback.loading = false;
                            this.feedback.stage = null;
                        }
                    }
                },

                async restoreStoredSession(silent = false) {
                    if (this.feedback.loading) {
                        return;
                    }

                    const token = await this.readStoredCredential();

                    if (! token) {
                        this.storage.hasToken = false;
                        this.state = 'unauthenticated';

                        if (! silent) {
                            this.feedback.error = 'לא נמצא credential שמיש ב־SecureStorage.';
                        }

                        return;
                    }

                    await this.bootstrapWithToken(token, silent);
                },

                async bootstrapWithToken(token, silent = false) {
                    this.feedback.loading = true;
                    this.feedback.stage = 'bootstrap';
                    this.state = 'syncing';

                    try {
                        const response = await window.fetch(this.api.bootstrap_url, {
                            headers: this.remoteJsonHeaders(token),
                        });

                        const payload = await this.parseJson(response);

                        if (response.ok) {
                            this.bootstrap = payload;
                            this.feedback.error = null;
                            this.feedback.validation = {};
                            this.state = 'authenticated';

                            return;
                        }

                        if (response.status === 401 || response.status === 403) {
                            await this.handleRevokedState(this.extractMessage(payload, 'השרת דחה את ה־credential.'));

                            return;
                        }

                        this.bootstrap = null;
                        this.state = 'unauthenticated';

                        if (! silent) {
                            this.feedback.error = this.extractMessage(payload, 'לא ניתן היה להשלים bootstrap מול השרת.');
                        }
                    } catch (error) {
                        this.bootstrap = null;
                        this.state = 'unauthenticated';

                        if (! silent) {
                            this.feedback.error = 'לא ניתן היה להשלים bootstrap מול השרת.';
                        }
                    } finally {
                        this.feedback.loading = false;
                        this.feedback.stage = null;
                    }
                },

                async persistSecureCredential(token) {
                    if (! this.session?.store_url || ! window.fetch) {
                        return false;
                    }

                    try {
                        const response = await window.fetch(this.session.store_url, {
                            method: 'PUT',
                            headers: this.localJsonHeaders(),
                            body: JSON.stringify({ access_token: token }),
                        });

                        const payload = await this.parseJson(response);

                        if (! response.ok) {
                            return false;
                        }

                        this.storage.available = Boolean(payload.available ?? true);
                        this.storage.hasToken = Boolean(payload.has_token ?? true);
                        this.storage.checked = true;

                        return true;
                    } catch (error) {
                        return false;
                    }
                },

                async clearStoredCredential() {
                    if (this.feedback.loading) {
                        return;
                    }

                    await this.deleteStoredCredential();
                    this.bootstrap = null;
                    this.feedback.error = null;
                    this.feedback.validation = {};
                    this.state = 'unauthenticated';
                },

                async handleRevokedState(message) {
                    await this.deleteStoredCredential();
                    this.bootstrap = null;
                    this.feedback.validation = {};
                    this.feedback.error = message;
                    this.state = 'revoked';
                },

                async deleteStoredCredential() {
                    if (! this.session?.destroy_url || ! window.fetch) {
                        this.storage.hasToken = false;

                        return false;
                    }

                    try {
                        await window.fetch(this.session.destroy_url, {
                            method: 'DELETE',
                            headers: this.localJsonHeaders(),
                        });
                    } catch (error) {
                    }

                    this.storage.hasToken = false;

                    return true;
                },

                async readStoredCredential() {
                    if (! this.storage.available || ! this.session?.access_token_key) {
                        return null;
                    }

                    if (! window.NativePHPMobile?.secureStorage?.get) {
                        return null;
                    }

                    try {
                        const result = await window.NativePHPMobile.secureStorage.get(this.session.access_token_key);

                        if (typeof result?.value !== 'string' || result.value.length === 0) {
                            return null;
                        }

                        return result.value;
                    } catch (error) {
                        return null;
                    }
                },

                localJsonHeaders() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                    return {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                },

                remoteJsonHeaders(token = null) {
                    const headers = {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    };

                    if (token) {
                        headers.Authorization = `Bearer ${token}`;
                    }

                    return headers;
                },

                async parseJson(response) {
                    const contentType = response.headers?.get?.('content-type') ?? '';

                    if (! contentType.includes('application/json')) {
                        return {};
                    }

                    try {
                        return await response.json();
                    } catch (error) {
                        return {};
                    }
                },

                extractMessage(payload, fallback) {
                    if (typeof payload?.message === 'string' && payload.message.length > 0) {
                        return payload.message;
                    }

                    if (payload?.errors && typeof payload.errors === 'object') {
                        const firstMessage = Object.values(payload.errors)
                            .flat()
                            .find((value) => typeof value === 'string' && value.length > 0);

                        if (firstMessage) {
                            return firstMessage;
                        }
                    }

                    return fallback;
                },

                validationMessage(field) {
                    const fieldErrors = this.feedback.validation?.[field] ?? [];

                    if (! Array.isArray(fieldErrors) || fieldErrors.length === 0) {
                        return '';
                    }

                    return fieldErrors[0];
                },

                get stageLabel() {
                    if (this.feedback.stage === 'login') {
                        return 'Login';
                    }

                    if (this.feedback.stage === 'bootstrap') {
                        return 'Bootstrap';
                    }

                    return 'Working';
                },

                get activeState() {
                    return this.states[this.state] ?? {
                        eyebrow: 'Unknown',
                        title: 'Unknown mobile shell state.',
                        description: 'The state model has not been initialized yet.',
                        hints: [],
                    };
                },
            }));
        });
    </script>
</x-layouts.mobile-shell>
