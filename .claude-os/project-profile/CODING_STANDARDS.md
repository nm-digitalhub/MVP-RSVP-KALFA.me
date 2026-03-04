# Coding Standards — Kalfa RSVP

## PHP

- **Strict types:** `declare(strict_types=1);` at top of every PHP file (project standard).
- **Namespace / PSR-4:** `App\` → `app/`; one class per file; class name matches filename.
- **Naming:** PascalCase for classes; camelCase for methods and variables; UPPER_SNAKE for constants.
- **Controllers:** Single-action or resource; type-hint requests and models; return View, JsonResponse, or redirect.
- **Models:** Eloquent; `$fillable` or guarded; `casts()` for enums, arrays, dates; relations as methods.
- **Services:** Prefer final classes; constructor injection; no static facades for domain logic.
- **Comments:** Docblocks for public methods and non-obvious logic; avoid redundant comments.

## Blade / Views

- **Layout:** `@extends('layouts.app')` or `layouts.guest`; `@section('title')`, optional `@section('header')`, `@section('content')`.
- **Components:** `<x-name />` or `<livewire:name />`; props and slots as needed.
- **RTL:** Authenticated app layout sets `dir="rtl"` on `<html>` (Hebrew).
- **Tailwind:** Utility-first; prefer Tailwind classes; shared components in `resources/views/components/`.
- **Livewire:** Reference by kebab-case: `livewire:dashboard`, `livewire:organizations.index`, `@livewire('system.dashboard')`.

## Livewire

- **Components:** Under `app/Livewire/`; subdirectories by feature (e.g. `System/`, `Organizations/`, `Profile/`).
- **Layout/Title:** Use attributes when full-page: `#[Layout('layouts.app')]`, `#[Title('...')]`.
- **Views:** One view per component under `resources/views/livewire/` (mirror namespace path).
- **State:** Public properties for bindings; avoid unnecessary global state; use `mount()` for initial data.
- **Actions:** Public methods for user actions; validate then redirect or emit as appropriate.

## Frontend (JS/CSS)

- **Build:** Vite 7; entry `resources/js/app.js`, `resources/css/app.css`; alias `@/` → `resources/js`.
- **Tailwind v4:** Via `@tailwindcss/vite`; no inline scripts for styling when avoidable.
- **Alpine.js:** For client interactivity (e.g. mobile drawer); keep logic minimal in Blade.

## CSS / Tailwind

- **Entry:** Single file `resources/css/app.css` with `@import "tailwindcss"` only; no `tailwind.config.js` at project root (Tailwind v4 CSS-first).
- **Styling method:** Utility-only Tailwind in Blade and Livewire views; no `@apply` or custom CSS layers in app CSS.
- **Colors:** Prefer Tailwind palette: `gray-*` neutrals, `indigo-*` primary, `red-*` danger, `green-*` success; avoid low-contrast borders in light mode.
- **Interactivity:** Visible focus on controls/links (`focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2`); touch targets ≥44px (`min-h-[44px]`); short transitions (`transition-colors duration-200`).
- **RTL:** Layout sets `dir="rtl"`; use RTL-aware or logical utilities where layout depends on direction.
- **Details:** See `.claude-os/project-profile/CSS_AND_STYLING.md`.

## File organization

- **Routes:** `routes/web.php` (web + system), `routes/api.php`, `routes/auth.php`.
- **Config:** Single source of truth in `config/`; override via `.env` only where documented (e.g. billing gateway).
- **Policies:** `app/Policies/`; register in `AuthServiceProvider` or auto-discovery.
- **Enums:** `app/Enums/` for status and role enums; used in model casts and policies.

## Linting / formatting

- **PHP:** Laravel Pint (`php artisan pint`); project uses 4-space indent, PSR-12–style.
- **EditorConfig:** `.editorconfig` present (charset, EOL, indent, trim).
