# CSS and Styling — Kalfa RSVP

Analysis of the project’s CSS architecture, tooling, and conventions (focus: CSS).

---

## 1. Stack and entry points

| Item | Value | Evidence |
|------|--------|----------|
| **CSS entry** | `resources/css/app.css` | `vite.config.js` → `input: ['resources/css/app.css', 'resources/js/app.js']` |
| **Tailwind** | v4.x | `package.json`: `"tailwindcss": "^4.0.0"`, `"@tailwindcss/vite": "^4.1.18"` |
| **Build** | Vite 7 | `vite.config.js`; plugin `tailwindcss()` from `@tailwindcss/vite` |
| **CSS transform** | LightningCSS | `vite.config.js` → `css.transformer: 'lightningcss'`, `cssMinify: true`, `cssCodeSplit: true` |
| **No Tailwind config file** | Yes | No `tailwind.config.js` at repo root; Tailwind v4 uses CSS-first config |

---

## 2. Tailwind v4 usage

### 2.1 Setup

- **Plugin:** `import tailwindcss from '@tailwindcss/vite'` in `vite.config.js`; no PostCSS config for Tailwind.
- **CSS entry:** `resources/css/app.css` contains only:
  ```css
  @import "tailwindcss";
  ```
- **No `@theme` in app CSS:** Theme uses Tailwind’s default palette (no custom design tokens in the main app).
- **RTL:** Layout sets `dir="rtl"` on `<html>` (`layouts/app.blade.php`); Tailwind RTL variants (e.g. `text-right`) are used where needed in views.

### 2.2 Patterns in views

- **Utility-first:** All styling is via Tailwind utility classes in Blade/Livewire views.
- **Common patterns:**
  - Layout: `max-w-7xl mx-auto px-4 py-8`, `max-w-4xl`, `max-w-2xl`, `max-w-lg` for containers; `@yield('containerWidth', 'max-w-7xl')` in layout.
  - Cards: `bg-white rounded-xl shadow-sm border border-gray-200`, `rounded-lg`, `overflow-hidden`.
  - Typography: `text-gray-900` (headings), `text-gray-600` / `text-gray-500` (secondary), `text-sm`, `text-lg`, `font-medium`, `font-semibold`.
  - Buttons/links: `bg-indigo-600 hover:bg-indigo-700`, `border border-gray-300`, `rounded-lg` / `rounded-md`, `focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2`, `transition-colors duration-200`, `cursor-pointer`, `min-h-[44px]` for touch targets.
  - Spacing: `space-y-6`, `gap-4`, `p-6`, `px-4 py-3`.
  - Status badges: `inline-flex px-2.5 py-1 text-xs font-medium rounded-full` + semantic colors (`bg-green-100 text-green-800`, `bg-amber-100 text-amber-800`, etc.).

### 2.3 Components

- Reusable Blade components in `resources/views/components/` use Tailwind via `$attributes->merge(['class' => '...'])` (e.g. `primary-button`, `text-input`, `input-label`, `textarea`).
- No component-level CSS files; no `<style>` in Blade except where required by third-party snippets.

---

## 3. Build and output

- **Output:** `public/build/`; CSS emitted as hashed assets (`assets/css/[name]-[hash].css` per `vite.config.js`).
- **Injection:** Layouts use `@vite(['resources/css/app.css', 'resources/js/app.js'])`; single global CSS bundle.
- **No CSS-in-JS:** No styled-components, Emotion, or Vue/React scoped styles; all styling is Tailwind in Blade.

---

## 4. Third-party and JS

- **Flowbite:** `import 'flowbite'` in `resources/js/app.js`; Flowbite 4 provides UI components (e.g. dropdowns, modals) and may inject or expect Tailwind-compatible classes.
- **Alpine.js:** Used for interactivity (e.g. mobile menu); no Alpine-specific CSS; layout and look come from Tailwind.
- **jQuery:** Present for legacy or specific scripts; styling remains Tailwind-driven.

---

## 5. Conventions (for consistency)

- **Colors:** Prefer Tailwind semantic names: `gray-*` for neutrals, `indigo-*` for primary actions, `red-*` for danger, `green-*` for success.
- **Borders:** `border-gray-200` for cards/sections; avoid low-contrast borders (e.g. `border-white/10`) in light mode.
- **Focus:** Visible focus rings for keyboard navigation: `focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2` (avoids ring on mouse click; use `focus:ring-*` only when focus ring must show on every focus).
- **Touch:** Minimum tap targets ~44px: `min-h-[44px]` (and `min-w-[44px]` where appropriate) on buttons and primary links.
- **Transitions:** Short, non-layout: `transition-colors duration-200` (avoid animating width/height for performance).
- **RTL:** Use logical or RTL-aware utilities where layout depends on direction; main layout is RTL via `dir="rtl"`.

---

## 6. What is not used

- No `tailwind.config.js` in application root (only inside `claude-os/frontend/`, which is separate tooling).
- No `@theme` or custom design tokens in `resources/css/app.css`.
- No Sass/SCSS or Less; no separate “theme” or “variables” CSS file in the main app.
- No Tailwind `@layer components` or `@apply` in the main app CSS.

---

## 7. Example views (convention-aligned)

These views follow the conventions above and can be used as references:

- **`resources/views/dashboard/events/show.blade.php`** — Event detail: sections with `aria-labelledby`, status badge, buttons/links with `min-h-[44px]`, `focus-visible:ring-2`, `transition-colors duration-200`, `cursor-pointer`; management cards as full-card links with hover/focus-visible and semantic heading hierarchy.
- **`resources/views/pages/organizations/create.blade.php`** + **`resources/views/livewire/organizations/create.blade.php`** — Page pattern (containerWidth, page-header) + Livewire form: `x-input-label`/`x-text-input`, touch targets, focus-visible on buttons/links, `rounded-xl shadow-sm border border-gray-200` card.

## 8. File reference

| Path | Role |
|------|------|
| `resources/css/app.css` | Only Tailwind import; single global CSS entry |
| `vite.config.js` | Vite + Tailwind plugin + LightningCSS; defines CSS output and entry |
| `resources/views/layouts/app.blade.php` | Loads Vite assets; `body` classes: `bg-[#F9FAFB] antialiased text-gray-900` |
| `resources/views/layouts/guest.blade.php` | Guest layout; also uses Vite for CSS/JS |
| `resources/views/components/*.blade.php` | Buttons, inputs, navbar, etc.; all Tailwind classes |

---

## 9. Summary

- **Single global CSS entry:** `resources/css/app.css` → `@import "tailwindcss"` only.
- **Tailwind v4** via `@tailwindcss/vite`; no JS config; optional future use of `@theme` in that file for design tokens.
- **Styling is 100% utility classes** in Blade/Livewire views and in shared Blade components.
- **Build:** Vite 7, LightningCSS, CSS minification and code-splitting; output under `public/build/`.
- **RTL:** Handled at layout level (`dir="rtl"`); Tailwind utilities used accordingly in views.
