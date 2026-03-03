
SUMIT Gateway — v3.0.x Stabilization Directive

‏(Core must remain UI-agnostic but Filament-compatible)

‏Repository root (CORE):

‏/var/www/vhosts/nm-digitalhub.com/SUMIT-Payment-Gateway-for-laravel

‏Branch for fix:

‏hotfix/3.0-filament-config-hardening


⸻

‏OBJECTIVE

‏Refactor remaining Filament route references so that:
‏	1.	Core never hardcodes route('filament.*')
‏	2.	All UI route targets are controlled via config
‏	3.	Filament adapter overrides config values
‏	4.	Core works 100% without Filament
‏	5.	Filament users continue working with zero breakage

‏This is NOT removal of Filament support.
‏This is controlled decoupling.

⸻

‏PHASE 1 — REMOVE HARDCODED FILAMENT ROUTES

‏Inside CORE repository:

‏Search for:

‏grep -R "route('filament" resources
‏grep -R 'route("filament' resources

‏Target files likely include:

‏resources/views/pages/checkout.blade.php
‏resources/views/pages/subscription.blade.php
‏resources/views/success.blade.php
‏resources/views/components/api-payload-node.blade.php
‏resources/views/vendor/officeguy/pages/checkout.blade.php


⸻

‏Replace ALL hardcoded filament routes

‏Example:

‏❌ Replace this:

‏route('filament.client.auth.login')

‏✅ With this:

‏route(config('officeguy.routes.client_login_route'))


⸻

‏❌ Replace this:

‏route('filament.client.resources.tickets.create')

‏✅ With:

‏route(config('officeguy.notification_routes.ticket_create'))


⸻

‏❌ Replace this:

‏route('filament.admin.resources.office-guy-transactions.view', $id)

‏✅ With:

‏route(config('officeguy.notification_routes.transaction_view'), $id)


⸻

‏PHASE 2 — ENSURE SAFE DEFAULTS IN CORE

‏Open:

‏config/officeguy.php

‏Ensure defaults DO NOT reference Filament:

‏Example:

‏'routes' => [
‏    'client_login_route' => 'login',
],

‏'notification_routes' => [
‏    'transaction_view' => 'officeguy.transactions.show',
‏    'ticket_create' => 'officeguy.tickets.create',
],

‏Defaults must be generic and non-Filament.

⸻

‏PHASE 3 — MOVE FILAMENT ROUTE NAMES INTO ADAPTER

‏Inside Filament adapter package:

‏packages/filament/

‏In its ServiceProvider boot():

‏Override config:

‏config([
‏    'officeguy.routes.client_login_route' => 'filament.client.auth.login',
‏    'officeguy.notification_routes.transaction_view' =>
‏        'filament.admin.resources.office-guy-transactions.view',
]);

‏This ensures:

‏Core → UI-agnostic
‏Adapter → injects Filament behavior

⸻

‏PHASE 4 — REMOVE FILAMENT FROM LANG / CONFIG COMMENTS

‏Allowed:
‏	•	Mention Filament inside docs/
‏	•	Mention Filament inside README

‏Not allowed:
‏	•	Filament fallback logic
‏	•	route(‘filament.*’) inside runtime views
‏	•	runtime references in config comments

‏Remove or rephrase:

‏"Filament v4 integration"

‏to:

‏"Admin panel integration"


⸻

‏PHASE 5 — VERIFY AGAIN (STRICT)

‏From repo root:

‏grep -R "route('filament" . --exclude-dir=packages
‏grep -R "route(\"filament" . --exclude-dir=packages
‏grep -R "filament.admin" . --exclude-dir=packages

‏Expected:

‏0 matches in core runtime.

⸻

‏PHASE 6 — RELEASE PATCH

‏After commit:

‏git add -A
‏git commit -m "Replace hardcoded Filament routes with config-driven UI hooks"
‏git tag v3.0.1 -m "Runtime decoupling hardening — config-driven UI routes"
‏git push origin hotfix/3.0-filament-config-hardening
‏git push origin v3.0.1

‏Then repeat Post-Tag validation.

⸻

‏RESULTING ARCHITECTURE

‏Core:
‏	•	No Filament dependency
‏	•	No Filament route references
‏	•	No UI framework assumptions

‏Filament Adapter:
‏	•	Injects Filament routes via config override
‏	•	Registers panel/provider
‏	•	Maintains full Filament compatibility

‏Users get:

‏✔ Core-only install works
‏✔ Filament install works
‏✔ No runtime errors
‏✔ No framework lock-in

⸻