═══════════════════════════════════════════════════════════════════════════════
COMPREHENSIVE FRONTEND ANALYSIS - KALFA.ME (Laravel/Livewire/Alpine.js App)
═══════════════════════════════════════════════════════════════════════════════

═══════════════════════════════════════════════════════════════════════════════
1. BLADE LAYOUTS (resources/views/layouts/)
═══════════════════════════════════════════════════════════════════════════════

FILE: resources/views/layouts/app.blade.php
- Authenticated user layout - main wrapper for dashboard & authenticated pages
- Features:
  * HTML5 docstring with localization support
  * Viewport/color-scheme meta tags
  * Theme attribute persistence via session
  * CSRF token in meta tag
  * @vite(['resources/css/app.css', 'resources/js/app.js']) - CSS+JS injection
  * PWA head support (@PwaHead)
  * Dark mode support (data-theme attribute)
  * Body classes: min-h-screen, bg-gray-50 dark:bg-gray-900, antialiased
  * Includes @livewireScripts and @stack('scripts')
  * RTL support (dir attribute based on isRTL() helper)
  * Sticky notification bar with x-data for show/hide

FILE: resources/views/layouts/guest.blade.php
- Unauthenticated user layout - login/register/auth pages
- Features:
  * Similar meta setup but simpler structure
  * No navbar, centered layout
  * @livewireStyles and @livewireScripts
  * Centered content container (min-h-screen px-4 py-8)
  * Dark mode support
  * RTL compatible

═══════════════════════════════════════════════════════════════════════════════
2. ALL BLADE VIEWS (resources/views/) BY SUBDIRECTORY
═══════════════════════════════════════════════════════════════════════════════

AUTHENTICATION VIEWS (auth/)
├── auth/login.blade.php - User login form with email/password
├── auth/register.blade.php - User registration form
├── auth/forgot-password.blade.php - Password reset request
├── auth/reset-password.blade.php - Password reset form
├── auth/confirm-password.blade.php - Password confirmation on sensitive actions
├── auth/verify-email.blade.php - Email verification flow
└── auth/change-password.blade.php - Change password form

DASHBOARD VIEWS (dashboard/)
├── dashboard/index.blade.php - Main authenticated dashboard landing
├── dashboard/events/
│   ├── create.blade.php - Event creation form
│   ├── edit.blade.php - Event editing form
│   ├── show.blade.php - Event detail view
│   ├── guests.blade.php - Event guests management (Livewire)
│   ├── invitations.blade.php - Event invitations management (Livewire)
│   ├── seat-assignments.blade.php - Guest seating assignment (Livewire)
│   └── tables.blade.php - Event tables management (Livewire + Sortable.js)
└── organizations/
    └── edit.blade.php - Organization settings/edit form

PAGES VIEWS (pages/)
├── pages/dashboard.blade.php - Livewire dashboard wrapper
├── pages/billing/
│   ├── account.blade.php - Billing account overview
│   ├── entitlements.blade.php - Product entitlements page
│   ├── intents.blade.php - Billing intents/invoices
│   └── usage.blade.php - Usage statistics page
└── pages/organizations/
    ├── create.blade.php - Organization creation
    └── index.blade.php - Organizations list

LIVEWIRE COMPONENT VIEWS (livewire/)
├── livewire/dashboard.blade.php - Main dashboard Livewire component (stats, events)
├── livewire/accept-invitation.blade.php - Organization invitation acceptance
├── livewire/tree-node.blade.php - Tree node component
├── livewire/tree-branch.blade.php - Tree branch component
├── livewire/dashboard/
│   ├── event-guests.blade.php - Event guests table with filtering/search
│   ├── event-invitations.blade.php - Invitation management table
│   ├── event-seat-assignments.blade.php - Seat assignment manager
│   ├── event-tables.blade.php - Tables list (list + seating chart view mode)
│   └── organization-members.blade.php - Organization members list
├── livewire/billing/
│   ├── account-overview.blade.php - Billing account info/payment method
│   ├── billing-intents-index.blade.php - Invoices/billing intents list
│   ├── entitlements-index.blade.php - Entitlements data table
│   └── usage-index.blade.php - Usage metrics display
├── livewire/profile/
│   ├── delete-user-form.blade.php - Account deletion confirmation form
│   ├── manage-passkeys.blade.php - WebAuthn passkey management
│   ├── update-password-form.blade.php - Password change form
│   └── update-profile-information-form.blade.php - Profile editing form
├── livewire/organizations/
│   ├── create.blade.php - Organization creation wizard
│   └── index.blade.php - Organizations directory
├── livewire/system/ (Admin-only)
│   ├── dashboard.blade.php - System admin dashboard
│   ├── accounts/
│   │   ├── create-account-wizard.blade.php - Account creation wizard
│   │   ├── index.blade.php - Accounts listing
│   │   └── show.blade.php - Account details (includes Sumit payment gateway)
│   ├── organizations/
│   │   ├── index.blade.php - System-wide organizations list
│   │   └── show.blade.php - Organization details
│   ├── products/
│   │   ├── index.blade.php - Products listing with filtering
│   │   ├── show.blade.php - Product detail view
│   │   ├── create-product-wizard.blade.php - Product creation (Sortable.js for plans)
│   │   ├── product-tree.blade.php - Hierarchical product tree (Sortable.js)
│   │   └── partials/
│   │       ├── product-card.blade.php - Product card display
│   │       ├── product-status-badge.blade.php - Status indicator
│   │       └── entitlement-row.blade.php - Entitlement list item
│   ├── users/
│   │   ├── index.blade.php - Users listing
│   │   └── show.blade.php - User detail view
│   └── settings/
│       └── index.blade.php - System settings management

EVENT PAGES (events/)
└── events/show.blade.php - Public event detail (RSVP page)

CHECKOUT VIEWS (checkout/)
├── checkout/tokenize.blade.php - Payment tokenization (Sumit gateway)
└── checkout/status.blade.php - Payment status/result page

RSVP VIEWS (rsvp/)
├── rsvp/show.blade.php - RSVP form for public event
└── rsvp/event-not-available.blade.php - Event not available message

SYSTEM VIEWS (system/)
├── system/dashboard.blade.php - System admin dashboard wrapper
├── system/organizations/index.blade.php - Admin organizations list
└── system/users/index.blade.php - Admin users list

TWILIO VIEWS (twilio/)
├── twilio/calling.blade.php - Calling interface
└── twilio/twiml/connect.blade.php - TwiML response handler

MISC VIEWS
├── welcome.blade.php - Public landing page
├── profile.blade.php - User profile wrapper
└── errors/
    ├── 403.blade.php - Forbidden error
    ├── 404.blade.php - Not found error
    ├── 429-payment.blade.php - Rate limit/payment required error
    └── 500.blade.php - Server error

EMAIL TEMPLATES (emails/)
├── emails/welcome-organizer.blade.php - Welcome email for new organizers
└── emails/organization-invitation.blade.php - Org invitation email

═══════════════════════════════════════════════════════════════════════════════
3. BLADE COMPONENTS (resources/views/components/) - 50+ COMPONENTS
═══════════════════════════════════════════════════════════════════════════════

LAYOUT COMPONENTS
├── components/layouts/app.blade.php - App layout wrapper
└── components/layouts/guest.blade.php - Guest layout wrapper

BUTTON COMPONENTS
├── components/primary-button.blade.php - Primary action button (brand color)
├── components/secondary-button.blade.php - Secondary button (outline style)
└── components/danger-button.blade.php - Destructive action button (red)

FORM COMPONENTS
├── components/text-input.blade.php - Text/email/password input field
├── components/textarea.blade.php - Multi-line text area
├── components/input-label.blade.php - Form label
├── components/input-error.blade.php - Error message display
└── components/file-upload-modern.blade.php - Modern file upload with preview

UI FEEDBACK COMPONENTS
├── components/action-message.blade.php - Temporary success/info message
├── components/auth-session-status.blade.php - Authentication status alerts
├── components/loading-skeleton.blade.php - Skeleton loader placeholder
└── components/empty-state.blade.php - Empty state UI

MODAL & OVERLAY COMPONENTS
└── components/modal.blade.php - Modal dialog with Alpine.js (x-data, x-show, focus trap)

NAVIGATION COMPONENTS
├── components/dynamic-navbar.blade.php - Main navigation bar (desktop + mobile)
└── components/dark-mode-toggle.blade.php - Dark/light theme switcher

FEATURE COMPONENTS
├── components/page-header.blade.php - Page title and breadcrumb header
└── components/dark-mode-toggle.blade.php - Theme switcher with Alpine.js

TREE COMPONENTS
├── components/tree/tree.blade.php - Tree list container
└── components/tree/⚡tree-toolbar.blade.php - Tree control toolbar (expand/collapse)

ICON COMPONENTS (35+ Heroicons)
├── components/heroicon-o-*.blade.php
│   └── Examples: arrow-up, arrow-down, check, x-mark, chevron-down, chevron-right,
│       plus-circle, minus-circle, envelope, phone, map-pin, calendar-days,
│       magnifying-glass, bars-3, shield-check, lock-closed, information-circle,
│       exclamation-triangle, clipboard, cpu-chip, server, paint-brush, funnel,
│       hashtag, share-across, arrows-right-left, arrow-path, arrow-right-on-rectangle,
│       arrow-top-right-on-square, chat-bubble-left-right, x-circle

═══════════════════════════════════════════════════════════════════════════════
4. LIVEWIRE COMPONENTS (app/Livewire/) - DETAILED INVENTORY
═══════════════════════════════════════════════════════════════════════════════

ROOT LEVEL COMPONENTS

1. Dashboard.php
   - View: resources/views/livewire/dashboard.blade.php
   - Properties: 
     * organization (current user's active org)
     * events (org's events with guest counts)
     * totalEvents, totalGuests, upcomingEvent
     * organizationStatusBadge
   - Methods: mount() - redirects if no org, render()

2. AcceptInvitation.php
   - View: resources/views/livewire/accept-invitation.blade.php
   - Layout: @Layout('layouts.guest')
   - Properties: token (string), invitation (OrganizationInvitation)
   - Methods: mount(token), accept()
   - Business: Validates invitation, adds user to organization

3. Organizations/Index.php
   - View: resources/views/livewire/organizations/index.blade.php
   - Lists user's organizations with pagination

4. Organizations/Create.php
   - View: resources/views/livewire/organizations/create.blade.php
   - Wizard for creating new organization

═══════════════════════════════════════════════════════════════════════════════
DASHBOARD SUBCOMPONENTS (app/Livewire/Dashboard/)

1. EventInvitations.php
   - View: resources/views/livewire/dashboard/event-invitations.blade.php
   - Properties: eventId (int), event (Event model)
   - Features: Invitation list, management actions

2. EventSeatAssignments.php
   - View: resources/views/livewire/dashboard/event-seat-assignments.blade.php
   - Properties: eventId, guestId
   - Features: Assign guests to seats/tables

3. EventGuests.php
   - View: resources/views/livewire/dashboard/event-guests.blade.php
   - Properties: eventId, search, sortBy, sortDirection
   - Uses: WithPagination trait
   - Features: Guest listing, filtering, search, RSVP status

4. EventTables.php
   - View: resources/views/livewire/dashboard/event-tables.blade.php
   - Properties: eventId, viewMode ('list' | 'chart')
   - Features: Table management, list view, seating chart visualization
   - Uses: Sortable.js for drag-drop

5. OrganizationMembers.php
   - View: resources/views/livewire/dashboard/organization-members.blade.php
   - Properties: organizationId, members list
   - Features: Member management, roles, permissions

═══════════════════════════════════════════════════════════════════════════════
BILLING SUBCOMPONENTS (app/Livewire/Billing/)

1. AccountOverview.php
   - View: resources/views/livewire/billing/account-overview.blade.php
   - Displays billing account info, payment method

2. BillingIntentsIndex.php
   - View: resources/views/livewire/billing/billing-intents-index.blade.php
   - Lists invoices/billing intents with pagination

3. EntitlementsIndex.php
   - View: resources/views/livewire/billing/entitlements-index.blade.php
   - Uses: WithPagination trait
   - Displays active entitlements with status

4. UsageIndex.php
   - View: resources/views/livewire/billing/usage-index.blade.php
   - Shows API/service usage metrics

═══════════════════════════════════════════════════════════════════════════════
PROFILE SUBCOMPONENTS (app/Livewire/Profile/)

1. UpdateProfileInformationForm.php
   - View: resources/views/livewire/profile/update-profile-information-form.blade.php
   - Updates user name, email

2. UpdatePasswordForm.php
   - View: resources/views/livewire/profile/update-password-form.blade.php
   - Password change with validation

3. DeleteUserForm.php
   - View: resources/views/livewire/profile/delete-user-form.blade.php
   - Account deletion with confirmation

4. ManagePasskeys.php
   - View: resources/views/livewire/profile/manage-passkeys.blade.php
   - WebAuthn/Passkey management for passwordless login

═══════════════════════════════════════════════════════════════════════════════
SYSTEM SUBCOMPONENTS (app/Livewire/System/) - ADMIN PANEL

1. Dashboard.php
   - System admin dashboard

ACCOUNTS SUBCOMPONENTS (app/Livewire/System/Accounts/)

1. Index.php
   - View: resources/views/livewire/system/accounts/index.blade.php
   - Lists all accounts with search/filtering
   - Uses: WithPagination

2. Show.php
   - View: resources/views/livewire/system/accounts/show.blade.php
   - Account details, payment info
   - Integrates Sumit payment gateway

3. CreateAccountWizard.php
   - View: resources/views/livewire/system/accounts/create-account-wizard.blade.php
   - Multi-step account creation wizard

ORGANIZATIONS SUBCOMPONENTS (app/Livewire/System/Organizations/)

1. Index.php
   - View: resources/views/livewire/system/organizations/index.blade.php
   - System-wide organization listing

2. Show.php
   - View: resources/views/livewire/system/organizations/show.blade.php
   - Organization management

PRODUCTS SUBCOMPONENTS (app/Livewire/System/Products/)

1. Index.php
   - View: resources/views/livewire/system/products/index.blade.php
   - Properties: search (string), filterStatus (ProductStatus enum), filterCategory
   - Uses: WithPagination
   - Features: Full-text search across products and SKUs, status filtering, category filtering

2. Show.php
   - View: resources/views/livewire/system/products/show.blade.php
   - Product detail view

3. CreateProductWizard.php
   - View: resources/views/livewire/system/products/create-product-wizard.blade.php
   - Multi-step product creation
   - Uses: Sortable.js for product plans ordering

4. ProductTree.php
   - View: resources/views/livewire/system/products/product-tree.blade.php
   - Hierarchical product tree display
   - Uses: Sortable.js for drag-drop reordering

5. ProductCard.php
   - View: resources/views/livewire/system/products/partials/product-card.blade.php
   - Product card display component

6. ProductStatusBadge.php
   - View: resources/views/livewire/system/products/partials/product-status-badge.blade.php
   - Status indicator (active/draft/archived)

7. EntitlementRow.php
   - View: resources/views/livewire/system/products/partials/entitlement-row.blade.php
   - Entitlement list row item

SETTINGS SUBCOMPONENTS (app/Livewire/System/Settings/)

1. Index.php
   - View: resources/views/livewire/system/settings/index.blade.php
   - System-wide settings management

USERS SUBCOMPONENTS (app/Livewire/System/Users/)

1. Index.php
   - View: resources/views/livewire/system/users/index.blade.php
   - System users listing
   - Uses: WithPagination

2. Show.php
   - View: resources/views/livewire/system/users/show.blade.php
   - User detail view

ACTIONS (app/Livewire/Actions/)

1. Logout.php
   - Handles user logout action

═══════════════════════════════════════════════════════════════════════════════
5. ALPINE.JS USAGE PATTERNS
═══════════════════════════════════════════════════════════════════════════════

Alpine PLUGINS INSTALLED (resources/js/app.js)
├── @alpinejs/collapse - Collapse plugin for expanding/collapsing content
├── @alpinejs/intersect - Intersection observer plugin
├── @ryangjchandler/alpine-clipboard - Clipboard copy functionality

ALPINE DIRECTIVES FOUND IN VIEWS

x-data (State Container)
├── Modal Component: x-data="{ show: @js($show), previouslyFocused: null, focusFirst(){...} }"
├── Dynamic Navbar: x-data="{ mobileMenuOpen: false }"
├── File Upload: x-data="modernFileUpload({ ... })"
├── Dark Mode Toggle: x-data="{ isDark: localStorage.getItem('theme') === 'dark' || ... }"
├── Dropdowns: x-data="{ open: false }"
├── Tree Toolbar: x-data="{ settingsOpen: false }"

x-show (Conditional Visibility)
├── Modal backdrop and content visibility
├── Mobile menu overlay and drawer
├── File upload areas (files.length === 0, files.length > 0)
├── Dark mode icons (isDark, !isDark)
├── Dropdown menus (open state)
├── Tree toolbar settings (settingsOpen)

@click (Event Handling)
├── Modal: x-on:click="show = false" (closes on backdrop click)
├── Mobile Menu: @click="mobileMenuOpen = false" (close on overlay)
├── Buttons: @click="open = !open" (toggle dropdowns)
├── File Upload: @click="$refs.fileInput.click()" (trigger file picker)
├── Tree: @click="$dispatch('tree-expand-all')" (custom Alpine events)

x-bind (Dynamic Binding)
├── Icons: x-bind:class="{ 'rotate-180': open }" (conditional CSS classes)
├── Attributes: x-bind:aria-label for accessibility

x-transition (Animations)
├── Modal: x-transition:enter/enter-start/enter-end/leave/leave-start/leave-end
│   └── Fade + scale animations on modal appearance
├── Dropdown: x-transition:enter="transition ease-out duration-200"
│   └── Fade + scale from top-right
├── Mobile Menu: x-transition:enter="transition-opacity ease-out duration-300"
│   └── Opacity animation for overlay

x-on:keydown (Keyboard Events)
├── Modal: x-on:keydown.escape.window="show = false" (ESC closes modal)
├── Tab Trap: JavaScript handles tab focus trapping in modals

x-cloak (Load Management)
├── Used with modals, dropdowns, dark mode toggle
└── Prevents FOUC (flash of unstyled content)

x-on:open-modal.window (Custom Events)
├── Modal: Listens for 'open-modal' window event
├── Dispatches modal opening with event.detail === modalName

x-on:close.window (Custom Close Events)
├── Modal: Closes and restores focus

x-on:click.away (Click Outside Detection)
├── Dropdowns: @click.away="open = false"
├── Settings panel: @click.outside="settingsOpen = false"

x-on:click.stop (Event Propagation Control)
├── Modal: x-on:click.stop prevents closing when clicking modal content

x-init (Initialization Hook)
├── Dark Mode: Applies theme on page load

x-id (Accessibility IDs)
├── Navbar: x-id="['navbar']" for aria-controls

x-nextTick (DOM Timing)
├── Modal: $nextTick(() => focusFirst()) and restoreFocus()

$refs (Element References)
├── File Upload: $refs.fileInput.click() - trigger hidden input
├── Generally used for DOM element access

$dispatch (Custom Events)
├── Tree: $dispatch('tree-expand-all') and $dispatch('tree-collapse-all')

$el (Element Context)
├── Modal: this.$el.querySelectorAll() for focus management

═══════════════════════════════════════════════════════════════════════════════
6. TAILWIND CONFIGURATION
═══════════════════════════════════════════════════════════════════════════════

CONFIG FILE: tailwind.config.js (Implicit v4, via @tailwindcss/vite plugin)

CUSTOM THEME VARIABLES (resources/css/app.css @theme block):
- Brand Colors:
  * --color-brand: #6C4CF1 (primary purple)
  * --color-brand-hover: #5A3DE0 (darker hover state)
  * --color-brand-light: #A594F9 (light brand for backgrounds)

- Neutral Colors:
  * --color-surface: #FAFAFC (page background)
  * --color-card: #FFFFFF (card/container background)
  * --color-stroke: #E4E4E7 (borders)

- Navigation:
  * --color-nav-bg: rgba(255, 255, 255, 0.9) (navbar background with transparency)
  * --color-nav-border: rgba(228, 228, 231, 0.8)
  * --color-nav-link: #4B5563
  * --color-nav-link-hover: #111827

- Typography:
  * --color-content: #18181B (main text)
  * --color-content-muted: #71717A (secondary text)

- Status Colors:
  * --color-success: #22C55E (green)
  * --color-warning: #F59E0B (amber)
  * --color-danger: #EF4444 (red)

- Shadows:
  * --shadow-brand: 0 20px 50px rgba(108, 76, 241, 0.15)
  * --shadow-danger: 0 20px 50px rgba(239, 68, 68, 0.15)
  * --shadow-nav: 0 4px 6px -1px rgb(0 0 0 / 0.1)

CUSTOM CSS LAYER COMPONENTS:
- .glass-navbar - Premium navbar with backdrop blur
- .mobile-drawer - Fixed position drawer for mobile nav (RTL compatible)
- .nav-link - Navigation link base styles
- .nav-link-active - Active nav link states
- Cropper.js integration styles (.cropper-view-box, .cropper-face, etc.)

DARK MODE SUPPORT:
- CSS variable overrides for .dark class
- Color palette adjustments for dark theme
- Used throughout with dark: prefix in Tailwind classes

RTL SUPPORT:
- [dir="rtl"] selectors for right-to-left layouts
- Mobile drawer transform adjustments
- Margin/padding direction-aware (me-, ms-, pe-, ps-)
- Language detection: isRTL() Laravel helper in layouts

═══════════════════════════════════════════════════════════════════════════════
7. VITE CONFIGURATION
═══════════════════════════════════════════════════════════════════════════════

FILE: vite.config.js

ENTRY POINTS:
- resources/css/app.css - Main stylesheet (Tailwind + custom)
- resources/js/app.js - Main JavaScript entry
- resources/js/passkey-login.js - WebAuthn/Passkey login flow

ALIASES:
- @ → resources/js (for import statements)

PLUGINS:
1. tailwindcss() - @tailwindcss/vite v4.2.1
2. laravel() - laravel-vite-plugin v2.0.0 with auto-refresh

CSS PROCESSING:
- Transformer: lightningcss (fast CSS processing)
- LightningCSS options: errorRecovery, customMedia drafts enabled
- PostCSS: empty plugins array

BUILD CONFIGURATION:
- sourcemap: false (production builds without sourcemaps)
- minify: 'esbuild' (JavaScript minification)
- cssMinify: true (CSS minification)
- cssCodeSplit: true (Split CSS per chunk)
- target: 'es2020' (modern JavaScript compatibility)
- outDir: 'public/build' (build output directory)
- emptyOutDir: true (clean output on build)
- chunkSizeWarningLimit: 4000 KB

BUILD OUTPUT:
- CSS: assets/css/[name]-[hash].css
- JS: assets/js/[name]-[hash].[ext]
- Compact output enabled

SERVER (Development):
- host: '0.0.0.0' (accessible from any network interface)
- port: 5173 (Vite default)
- strictPort: true (fail if port busy)
- HMR overlay: false (no error overlay)
- usePolling: true (filesystem watch polling for Docker/VM)

OPTIMIZATION:
- exclude: ['laravel-vite-plugin']
- include: ['flowbite', 'alpinejs', 'axios', '@laragear/webpass']
  └── Pre-bundles these deps for faster startup

ASSETS:
- Includes SVG, PNG, JPG, WOFF, WOFF2 as assets

═══════════════════════════════════════════════════════════════════════════════
8. JavaScript Dependencies (package.json)
═══════════════════════════════════════════════════════════════════════════════

DEVDEPENDENCIES:
┌─────────────────────────────────────────┐
│ Build Tools & CSS                       │
├─────────────────────────────────────────┤
│ @tailwindcss/vite: ^4.2.1               │
│ laravel-vite-plugin: ^2.0.0             │
│ tailwindcss: ^4.2.1                     │
│ vite: ^7.0.7                            │
├─────────────────────────────────────────┤
│ Formatting                              │
├─────────────────────────────────────────┤
│ prettier: ^3.8.1                        │
│ prettier-plugin-tailwindcss: ^0.7.2     │
├─────────────────────────────────────────┤
│ Real-time Features                      │
├─────────────────────────────────────────┤
│ laravel-echo: ^2.3.0  (WebSocket client)│
│ pusher-js: ^8.4.0     (Pusher WebSocket)│
└─────────────────────────────────────────┘

DEPENDENCIES:
┌─────────────────────────────────────────┐
│ Frontend Framework & Utilities           │
├─────────────────────────────────────────┤
│ alpinejs: ^3.15.4                       │
│ @alpinejs/collapse: ^3.15.8             │
│ @alpinejs/intersect: ^3.15.8            │
├─────────────────────────────────────────┤
│ HTTP & Networking                       │
├─────────────────────────────────────────┤
│ axios: ^1.11.0                          │
│ ws: ^8.19.0 (WebSocket client)          │
├─────────────────────────────────────────┤
│ UI Libraries & Components               │
├─────────────────────────────────────────┤
│ flowbite: ^4.0.1 (Tailwind components)  │
│ @floating-ui/dom: ^1.7.6 (Positioning) │
├─────────────────────────────────────────┤
│ Icon & Clipboard                        │
├─────────────────────────────────────────┤
│ @ryangjchandler/alpine-clipboard:^2.3.0│
├─────────────────────────────────────────┤
│ Image Processing                        │
├─────────────────────────────────────────┤
│ cropperjs: ^2.1.0 (Image cropping)      │
│ @cropper/element-canvas: ^2.1.0         │
├─────────────────────────────────────────┤
│ Authentication                          │
├─────────────────────────────────────────┤
│ @laragear/webpass: ^2.1.2 (WebAuthn)   │
├─────────────────────────────────────────┤
│ Data Visualization                      │
├─────────────────────────────────────────┤
│ chart.js: ^4.5.1 (Charts & graphs)      │
├─────────────────────────────────────────┤
│ Sorting & Lists                         │
├─────────────────────────────────────────┤
│ sortablejs: ^1.15.7 (Drag-drop lists)   │
├─────────────────────────────────────────┤
│ Utilities                               │
├─────────────────────────────────────────┤
│ lodash: ^4.17.23 (JS utilities)         │
│ clsx: ^2.1.1 (Conditional classes)      │
│ tailwind-merge: ^3.5.0 (CSS merging)    │
├─────────────────────────────────────────┤
│ Server & Services                       │
├─────────────────────────────────────────┤
│ express: ^5.2.1 (Node.js server)        │
│ dotenv: ^17.3.1 (Environment config)    │
├─────────────────────────────────────────┤
│ AI & Generative Models                  │
├─────────────────────────────────────────┤
│ @google/generative-ai: ^0.24.1 (Gemini)│
├─────────────────────────────────────────┤
│ Audio Processing                        │
├─────────────────────────────────────────┤
│ @tw2gem/audio-converter: ^1.0.1         │
├─────────────────────────────────────────┤
│ Motion & Animation                      │
├─────────────────────────────────────────┤
│ framer-motion: ^12.36.0                 │
├─────────────────────────────────────────┤
│ Developer Tools & Observability         │
├─────────────────────────────────────────┤
│ jquery: ^4.0.0                          │
│ obsidian: ^1.12.3 (Obsidian API)        │
│ obsidian-mcp-server: ^2.0.7             │
└─────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════════════════
9. JavaScript Files (resources/js/)
═══════════════════════════════════════════════════════════════════════════════

FILE: resources/js/app.js
- Application entry point
- 46 lines of setup code
- Imports:
  * ./bootstrap - Bootstrap configuration
  * flowbite - Flowbite components
  * @laragear/webpass - WebAuthn library
  * jquery - jQuery utility library
  * cropperjs - Image cropper
  * alpine-clipboard - Clipboard plugin
  * @alpinejs/intersect - Intersection observer
  * @alpinejs/collapse - Collapse plugin
  * sortablejs - Drag-drop library
  * chart.js - Charts library
  * @floating-ui/dom - Positioning utility
- Global Exports:
  * window.Webpass = Webpass (WebAuthn)
  * window.Sortable = Sortable (Drag-drop)
  * window.Chart = Chart (Charts)
  * window.FloatingUI = { computePosition, flip, shift, offset }
- Alpine Plugin Registration (document.addEventListener('alpine:init'))
  * Clipboard plugin
  * Intersect plugin
  * Collapse plugin
- Cropper Initialization
  * Auto-initializes on DOMContentLoaded if #image element exists

FILE: resources/js/bootstrap.js
- Initializes Axios and Livewire integration
- Not shown but typically sets up CSRF tokens and request defaults

FILE: resources/js/echo.js
- Laravel Echo configuration for WebSocket connections
- Likely configures Pusher or Reverb for real-time features

FILE: resources/js/passkey-login.js
- Entry point for WebAuthn/Passkey authentication
- Used alongside main app.js in login flows

═══════════════════════════════════════════════════════════════════════════════
10. DYNAMIC UI BEHAVIORS - Implementation Details
═══════════════════════════════════════════════════════════════════════════════

MODALS
├── Implementation: Alpine.js x-data + x-show
├── Component: resources/views/components/modal.blade.php
├── Features:
│   ├── Show/hide toggle via x-data state
│   ├── Backdrop click closes (x-on:click="show = false")
│   ├── ESC key closes (x-on:keydown.escape.window)
│   ├── Focus management (focusFirst, restoreFocus)
│   ├── Tab trap prevents focus escape
│   ├── Smooth animations (x-transition)
│   ├── Accessibility: aria-modal, aria-hidden, aria-labelledby
│   └── Livewire dispatch: open-modal.window, close.window events

DROPDOWNS
├── Implementation: Alpine.js x-data="{ open: false }"
├── Location: dynamic-navbar.blade.php (3 dropdown menus)
├── Features:
│   ├── Click to toggle open state
│   ├── Click-outside detection (@click.away="open = false")
│   ├── Animated opening (x-transition)
│   ├── Icon rotation animation (x-bind:class with rotate-180)
│   └── Multiple dropdowns: Organizations, System, User account

MOBILE MENU / HAMBURGER
├── Implementation: Alpine.js state management
├── Location: dynamic-navbar.blade.php
├── Features:
│   ├── Overlay background (x-show with opacity transition)
│   ├── Slide drawer for mobile (fixed position)
│   ├── Click-outside closes
│   ├── RTL support (transform adjustments)
│   └── Responsive (hidden on lg screens, visible on mobile)

DARK MODE TOGGLE
├── Implementation: Alpine.js with localStorage
├── Component: resources/views/components/dark-mode-toggle.blade.php
├── Features:
│   ├── localStorage persistence (theme key)
│   ├── System preference detection (prefers-color-scheme)
│   ├── Toggle button updates DOM class
│   ├── Conditional icon display (sun/moon)
│   └── Instant theme switch without reload

FILE UPLOAD COMPONENT
├── Implementation: Alpine.js modernFileUpload function
├── Component: resources/views/components/file-upload-modern.blade.php
├── Features:
│   ├── Drag-drop and click to upload
│   ├── Multiple files support
│   ├── File preview (images show thumbnails)
│   ├── Progress indicators (uploading state)
│   ├── Error handling with messages
│   ├── Success state display
│   └── Remove file button (index-based)

TREE EXPAND/COLLAPSE
├── Implementation: Alpine.js with @click.dispatch
├── Component: resources/views/components/tree/⚡tree-toolbar.blade.php
├── Features:
│   ├── Expand all button - dispatches 'tree-expand-all'
│   ├── Collapse all button - dispatches 'tree-collapse-all'
│   ├── Settings panel toggle
│   └── Custom event listeners on tree components

SORTABLE LISTS (Drag-Drop)
├── Implementation: Sortable.js library
├── Files Using It:
│   ├── livewire/dashboard/event-tables.blade.php
│   ├── livewire/system/products/create-product-wizard.blade.php
│   └── livewire/system/products/product-tree.blade.php
├── Features:
│   ├── Initialize: Sortable.create($el, { ... })
│   ├── Reorder items by drag-drop
│   ├── Ghost/animation config
│   ├── Likely triggers Livewire events on drop
│   └── Supports nested lists (product tree)

EVENT VIEWS
├── List View: Card-based event display with sorting
├── Seating Chart View: Visual grid of tables
│   ├── Toggle between list and chart modes
│   ├── Click mode buttons to switch
│   └── Chart shows table cards with guest counts

═══════════════════════════════════════════════════════════════════════════════
11. DRAG AND DROP FEATURES
═══════════════════════════════════════════════════════════════════════════════

LIBRARY: SortableJS v1.15.7

IMPLEMENTATIONS:

1. Event Tables (livewire/dashboard/event-tables.blade.php)
   - Sortable card list for table management
   - Reorder tables by dragging
   - Triggers Livewire event on reorder

2. Product Wizard (livewire/system/products/create-product-wizard.blade.php)
   - Drag-drop to order product plans
   - Uses: Sortable.create($el, { ... })
   - Manages product plan sequence

3. Product Tree (livewire/system/products/product-tree.blade.php)
   - Hierarchical drag-drop
   - Reorder product nodes in tree
   - Complex nested structure support

TYPICAL SORTABLE CONFIG:
- handle selector (drag handle element)
- ghost class (dragging visual)
- animation: 150 (animation duration)
- onEnd callback (Livewire event dispatch)

═══════════════════════════════════════════════════════════════════════════════
12. CHART LIBRARY
═══════════════════════════════════════════════════════════════════════════════

LIBRARY: Chart.js v4.5.1 (window.Chart global)

USAGE LOCATIONS:
- System dashboard (likely analytics charts)
- Account overview (billing/usage charts)
- Billing usage page (metrics visualization)

REFERENCES:
- Chart references found in:
  * livewire/system/dashboard.blade.php
  * livewire/system/accounts/show.blade.php (icon reference)

IMPLEMENTATION:
- Exposed globally via: window.Chart = Chart
- Likely initialized within Livewire components
- No specific chart.js configuration files found
- Uses default Chart.js v4 configuration

═══════════════════════════════════════════════════════════════════════════════
13. EXTERNAL LIBRARIES & CDN SCRIPTS
═══════════════════════════════════════════════════════════════════════════════

PAYMENT GATEWAY - SUMIT (Israeli payment processor)
├── Location: livewire/system/accounts/show.blade.php
├── Resources:
│   ├── <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
│   ├── <script src="https://app.sumit.co.il/scripts/payments.js"></script>
│   └── Also used in checkout/tokenize.blade.php
├── Purpose: Payment processing and tokenization
├── Integration: Likely Eloquent model with payment gateway package

API DOCUMENTATION - STOPLIGHT ELEMENTS
├── Location: resources/views/vendor/scramble/docs.blade.php
├── Script: <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
├── Purpose: Interactive API documentation UI (Scramble/Laravel)

NO OTHER MAJOR CDN DEPENDENCIES FOUND
- All JavaScript libraries bundled via npm/Vite
- No Bootstrap CDN, Bulma, or other CSS frameworks loaded
- Tailwind v4 used exclusively for styling

═══════════════════════════════════════════════════════════════════════════════
14. BLADE COMPONENTS & VIEW COMPONENTS (app/View/Components/)
═══════════════════════════════════════════════════════════════════════════════

PHP VIEW COMPONENTS (app/View/Components/):
1. AppLayout.php
   - Wraps authenticated pages
   - Provides app-specific layout structure
   - Documentation: documentation/app/View/Components/AppLayout.md

2. GuestLayout.php
   - Wraps guest/public pages
   - Provides guest-specific layout
   - Documentation: documentation/app/View/Components/GuestLayout.md

BLADE COMPONENTS (resources/views/components/)
See Section 3 above for comprehensive list of 50+ blade components.

COMPONENT PHILOSOPHY:
- Heavy use of anonymous Blade components
- @props() directive for component props
- Reusable UI elements throughout the app
- Heroicon library for consistent iconography
- Form elements abstracted as components
- Layout wrappers as components

═══════════════════════════════════════════════════════════════════════════════
15. ICON LIBRARY & SYSTEM
═══════════════════════════════════════════════════════════════════════════════

ICON LIBRARY: Heroicons v2 (Outline style)

IMPLEMENTATION:
- Blade icon components in resources/views/components/
- Naming convention: heroicon-o-{name}.blade.php
- 35+ Heroicon components found

ICONS USED:
├── Navigation
│   ├── arrow-right-on-rectangle (logout)
│   ├── arrow-up, arrow-down (pagination/sorting)
│   ├── chevron-down, chevron-right (dropdowns)
│   ├── bars-3 (hamburger menu)
│   └── arrow-top-right-on-square (external link)
├── Content/Editing
│   ├── pencil (edit)
│   ├── clipboard (copy)
│   ├── clipboard-document-check (completed)
│   ├── paint-brush (customize)
│   └── share-across (share)
├── Status/Feedback
│   ├── check (success)
│   ├── x-mark, x-circle (close/error)
│   ├── exclamation-triangle (warning)
│   ├── information-circle (info)
│   ├── shield-check (security/verified)
│   └── lock-closed (locked/private)
├── Communication
│   ├── envelope (email)
│   ├── phone (contact)
│   ├── chat-bubble-left-right (messaging)
│   └── arrow-path (refresh/sync)
├── Data/Organization
│   ├── funnel (filter)
│   ├── magnifying-glass (search)
│   ├── calendar-days (date/event)
│   ├── map-pin (location)
│   ├── hashtag (tags)
│   └── arrows-right-left (exchange/swap)
├── System/Settings
│   ├── cpu-chip (system)
│   ├── server (infrastructure)
│   ├── plus-circle, minus-circle (add/remove)
│   └── sun/moon (theme toggle)
└── UI Elements
    ├── chart-bar (analytics - referenced but component may be missing)
    └── presentation-chart-line (reporting)

USAGE PATTERN:
<x-heroicon-o-{name} class="w-5 h-5" />

═══════════════════════════════════════════════════════════════════════════════
16. CSS FILES & STYLING STRATEGY
═══════════════════════════════════════════════════════════════════════════════

PRIMARY CSS:
FILE: resources/css/app.css (245+ lines)

STRUCTURE:
├── @import "tailwindcss" (Tailwind core)
├── @theme block (Custom design tokens)
│   ├── Brand colors (#6C4CF1 primary)
│   ├── Neutral palette
│   ├── Navigation colors
│   ├── Typography colors
│   ├── Status colors (success, warning, danger)
│   └── Custom shadows
├── @layer base (HTML resets)
│   ├── html/body overflow-x: hidden
│   ├── RTL text-align
│   ├── Select direction inherit
│   └── Placeholder alignment
├── @layer components (Reusable classes)
│   ├── Cropper.js styling
│   ├── Glass navbar (.glass-navbar)
│   ├── Mobile drawer (.mobile-drawer)
│   ├── Navigation links (.nav-link, .nav-link-active)
│   ├── RTL-specific transforms
│   ├── Touch scrolling optimizations
│   └── Dark mode component overrides
└── .dark pseudo-class (Dark mode variables)

TAILWIND FEATURES USED:
- CSS variables from @theme
- Dark mode class strategy (.dark on html)
- RTL support via [dir="rtl"] selectors
- Component layer for DRY CSS
- Utility-first CSS with custom components

NO SEPARATE CSS FILES:
- No resources/css/admin.css
- No resources/css/dashboard.css
- Single app.css file (monolithic approach)

PACKAGE CSS:
- @tailwindcss/vite (handles Tailwind processing)
- flowbite package likely includes component CSS
- Cropper.js package includes styling

═══════════════════════════════════════════════════════════════════════════════
17. ADDITIONAL FRONTEND FEATURES
═══════════════════════════════════════════════════════════════════════════════

PWA (Progressive Web App):
├── Feature: @PwaHead tag in layouts/app.blade.php
├── Likely provides:
│   ├── PWA manifest
│   ├── App icons
│   ├── Offline support
│   └── Install prompts

WEBAUTHN/PASSKEYS:
├── Library: @laragear/webpass v2.1.2
├── Component: ManagePasskeys.php (app/Livewire/Profile/)
├── Implementation: Passwordless login via WebAuthn
├── View: resources/views/livewire/profile/manage-passkeys.blade.php

GOOGLE GENERATIVE AI:
├── Library: @google/generative-ai v0.24.1
├── Likely use: AI-powered features (possibly in system or content areas)
├── Integration: Probably Livewire component

AUDIO PROCESSING:
├── Library: @tw2gem/audio-converter v1.0.1
├── Use case: Audio upload/conversion
├── Possibly integrated in file uploads or calling feature

TWILIO INTEGRATION:
├── Views: resources/views/twilio/calling.blade.php
├── TwiML: resources/views/twilio/twiml/connect.blade.php
├── Feature: Phone calling/VoIP capability
├── Backend likely handles Twilio API calls

REAL-TIME FEATURES:
├── Library: laravel-echo v2.3.0
├── Library: pusher-js v8.4.0
├── WebSocket broadcast support
├── Event notifications (invitations, guest updates, etc.)

═══════════════════════════════════════════════════════════════════════════════
18. ACCESSIBILITY FEATURES
═══════════════════════════════════════════════════════════════════════════════

SEMANTIC HTML:
├── Modal: role="dialog", aria-modal="true", aria-hidden
├── Navigation: <nav> with aria-label
├── Buttons: type="button" specified

FOCUS MANAGEMENT:
├── Modal focus trap (Tab navigation)
├── focusFirst() and restoreFocus() methods
├── Prevents focus from leaving modal

KEYBOARD NAVIGATION:
├── ESC key closes modals
├── Tab cycling within modals
├── Click-away and @click.away for dropdowns

ARIA LABELS:
├── aria-label on interactive elements
├── aria-labelledby on modals
├── aria-hidden on decorative elements

COLOR CONTRAST:
├── Tailwind classes used (indigo, gray, etc.)
├── Brand color tested (likely WCAG AA compliant)

╔═══════════════════════════════════════════════════════════════════════════════╗
║                           SUMMARY & KEY INSIGHTS                             ║
╚═══════════════════════════════════════════════════════════════════════════════╝

ARCHITECTURE STYLE:
✓ Monolithic Livewire application
✓ Alpine.js for simple interactivity (modals, dropdowns, toggles)
✓ Livewire for dynamic server-driven UI (tables, forms, real-time)
✓ Tailwind CSS v4 with custom design tokens
✓ Vite for fast build pipeline

TECHNOLOGY STACK:
✓ Laravel 11+ (Livewire v4 compatible)
✓ Alpine.js v3.15
✓ Tailwind CSS v4.2
✓ Vite v7
✓ Chart.js for analytics
✓ Sortable.js for drag-drop
✓ WebAuthn for passwordless auth
✓ Pusher/Echo for real-time features

KEY FEATURES:
✓ Event management (RSVP, seating, guest management)
✓ Organization multi-tenancy
✓ Billing/subscription management
✓ Admin system for products, accounts, users
✓ Real-time notifications
✓ Passwordless authentication (Passkeys/WebAuthn)
✓ Dark mode support
✓ RTL language support
✓ PWA capabilities
✓ Payment processing (Sumit gateway)

DESIGN SYSTEM:
✓ Custom brand color: #6C4CF1 (purple)
✓ Component-based architecture (50+ Blade components)
✓ Glass morphism navbar with backdrop blur
✓ Dark/light mode with localStorage persistence
✓ Heroicon iconography (35+ icons)
✓ Accessible modal and dropdown patterns

DEPLOYMENT CONSIDERATIONS:
✓ Vite build with code splitting
✓ CSS/JS minification and chunking
✓ Optimized deps pre-bundling
✓ Public/build output directory
✓ sourcemap disabled (production)

═══════════════════════════════════════════════════════════════════════════════
