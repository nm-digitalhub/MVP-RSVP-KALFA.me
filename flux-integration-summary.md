## Flux integration follow-up

- Injected `@fluxAppearance` into `resources/views/components/layouts/enterprise-app.blade.php` so the enterprise shell renders Flux styles and metadata immediately after the Vite bundle (per Flux README).
- Added `@fluxScripts` plus `@livewireScripts` ahead of the closing `</body>` tag so the new profile component and other Livewire/Flux consumers boot correctly.
- Imported `vendor/livewire/flux/dist/flux.css` and declared the required `@custom-variant dark (&:where(.dark, .dark *));` inside `resources/css/app.css` to expose Flux’s component styles and dark-mode helpers.
- Rebuilt the frontend assets via `npm run build` to ensure the updated CSS bundle is emitted for deployment.
- Added a Flux callout summary inside `resources/views/profile.blade.php` that highlights the logged-in user’s joined date, email status, and last update, with badges, manual actions, RTL-aware direction, and locale-aware dates (via Carbon) so the profile page feels more sculpted by Flux no matter the locale.
- Replaced the remaining profile edit boxes (password fields, inline passkey rename, delete confirmation) with Flux field/input/error components so every text input matches the Flux styling system, added a base rule forcing `ui-field`/`[data-flux-field]` (plus `.input-base`/`.textarea-base`) to `box-sizing: border-box` and `outline-offset: -2px` so focus highlights don’t affect layout, and rebuilt the Vite assets (`app-CUI1R-6-.js` + new `app-B82TR4hb.css`) to publish the refined inputs.
- Added Hebrew translations for the new labels in `resources/lang/he/messages.php`/`resources/lang/he.json` and matching English strings in `resources/lang/en/messages.php`/`resources/lang/en.json`, keeping the UI localized regardless of locale.

### Next steps

- Re-run `npm run dev` or `composer run dev` locally whenever you change frontend assets again so the development server stays in sync.

### Next steps

- Re-run `npm run dev` or `composer run dev` locally whenever you change frontend assets again so the development server stays in sync.
