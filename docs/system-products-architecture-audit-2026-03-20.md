# System Products Architecture Audit

Date: 2026-03-20

Scope:
- `resources/views/livewire/tree-node.blade.php`
- `resources/views/components/⚡tree-node.blade.php`
- `resources/views/livewire/tree-branch.blade.php`
- `resources/views/livewire/system/products/product-tree.blade.php`
- `resources/views/livewire/system/products/show.blade.php`
- `app/Livewire/System/Products/ProductTree.php`
- `app/Livewire/System/Products/Show.php`
- `app/Models/ProductEntitlement.php`

Verification standard used:
- Every finding below was treated as untrusted until it had direct evidence.
- Evidence types used:
  - code-path tracing
  - repository search
  - browser logs
  - focused tests
  - live browser automation when reproducible

## Verified Findings

### 1. `Add Plan`, `Add Limit`, and `Add Feature` are broken from the tree UI

Severity: High

Status: Verified by code and browser automation

Evidence chain:
- The add button in the branch header dispatches a browser event string, not a Livewire method call:
  - [tree-branch.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/tree-branch.blade.php#L121)
- `product-tree` passes:
  - `add-action="requestAddPlan"`
  - `add-action="requestAddLimit"`
  - `add-action="requestAddFeature"`
  - from [product-tree.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/product-tree.blade.php#L123)
  - from [product-tree.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/product-tree.blade.php#L210)
  - from [product-tree.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/product-tree.blade.php#L250)
- `ProductTree` contains plain methods named `requestAddPlan`, `requestAddLimit`, `requestAddFeature`:
  - [ProductTree.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/ProductTree.php#L77)
  - [ProductTree.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/ProductTree.php#L104)
  - [ProductTree.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/ProductTree.php#L126)
- There is no listener anywhere for the dispatched browser events `requestAddPlan`, `requestAddLimit`, or `requestAddFeature`.
  - Verified with repository search returning no matches.
- The real listeners that open forms are different events:
  - `tree:open-add-limit` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L445)
  - `tree:open-add-feature` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L525)
  - `tree:open-add-plan` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L605)
- Browser automation on `/system/products/1` confirmed:
  - `Add Plan` button is visible
  - clicking it does not open any editor
  - zero `/livewire/update` requests were emitted

Impact:
- The add buttons look available but are dead from the tree UI.

### 2. The `price` flow exists in PHP but is unreachable from the current screen

Severity: High

Status: Verified by code-path tracing and repository search

Evidence chain:
- `ProductTree` contains `requestAddPriceForPlan()`:
  - [ProductTree.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/ProductTree.php#L92)
- `Show` contains:
  - `openAddPriceForm()` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L711)
  - `startEditPrice()` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L721)
  - `savePrice()` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L735)
  - `togglePrice()` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L770)
  - `deletePrice()` at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L780)
- Only `openAddPriceForm()` has an event listener.
- There are no view references for:
  - `showPriceForm`
  - `savePrice`
  - `pricePlanId`
  - `editingPriceId`
  - verified by repository search over `resources/views`
- The tree renders plan price as read-only metadata, not as editable price rows:
  - [product-tree.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/product-tree.blade.php#L157)

Impact:
- Price persistence logic exists, but there is no end-to-end CRUD path for price on this page.

### 3. Entitlement `constraints` do not round-trip through the editor

Severity: Medium

Status: Verified by code-path tracing

Evidence chain:
- `ProductEntitlement` persists `constraints`:
  - [ProductEntitlement.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/ProductEntitlement.php#L19)
  - casted at [ProductEntitlement.php](/var/www/vhosts/kalfa.me/httpdocs/app/Models/ProductEntitlement.php#L35)
- The tree displays a constraints count for entitlements:
  - [product-tree.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/product-tree.blade.php#L301)
- The entitlement form only binds:
  - `newFeatureKey`
  - `newLabel`
  - `newType`
  - `newValue`
  - `entitlementIsActive`
  - `newDescription`
  - from [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L544)
- `startEditEntitlement()` hydrates only those fields:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L385)
- `addEntitlement()` persists only those fields:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L361)

Impact:
- Existing constraints may influence badges in the tree, but they cannot be viewed or edited from this screen.

### 4. Entitlement `type` and `is_active` are persisted without explicit validation rules

Severity: Medium-Low

Status: Verified by code-path tracing

Evidence chain:
- The entitlement form collects `newType` and `entitlementIsActive`:
  - [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L569)
  - [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L584)
- `startEditEntitlement()` hydrates them:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L395)
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L397)
- `addEntitlement()` persists them:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L365)
- `entitlementRules()` validates only:
  - `newFeatureKey`
  - `newLabel`
  - `newValue`
  - `newDescription`
  - at [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L141)

Impact:
- These persisted fields rely on Livewire hydration and UI discipline, not on explicit server-side rule coverage.

## Verified Working Paths

### 1. Plan edit now opens a real editor panel

Evidence:
- The plan editor is rendered in:
  - [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L289)
- `tree:open-edit-plan` listener hydrates plan state in:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L613)
- Focused test proves the event populates state and renders the editor:
  - [ProductTreeViewTest.php](/var/www/vhosts/kalfa.me/httpdocs/tests/Feature/System/Products/ProductTreeViewTest.php)
  - `php artisan test --compact tests/Feature/System/Products/ProductTreeViewTest.php` passed with 2 tests / 21 assertions

### 2. Limit and feature editors now exist and are backed by Livewire save flows

Evidence:
- Limit editor view:
  - [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L394)
- Feature editor view:
  - [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L451)
- Limit handlers:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L453)
- Feature handlers:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L533)

### 3. Entitlement edit, toggle, and delete are now backed by real handlers

Evidence:
- Entitlement handlers:
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L385)
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L401)
  - [Show.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/Show.php#L412)
- Entitlement editor view:
  - [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L536)

## Hypotheses Requiring Further Verification

### 1. Nested `product-tree` may stay stale after parent-side saves

Status: Not promoted to a confirmed finding

Reason:
- There is code-level risk because the tree is a separate nested Livewire component:
  - [show.blade.php](/var/www/vhosts/kalfa.me/httpdocs/resources/views/livewire/system/products/show.blade.php#L287)
- The child holds `public Product $product;` with no visible reactive annotation:
  - [ProductTree.php](/var/www/vhosts/kalfa.me/httpdocs/app/Livewire/System/Products/ProductTree.php#L15)
- Parent save methods do not visibly remount or explicitly refresh the child.

Why this remains a hypothesis:
- It has not yet been reproduced reliably in browser automation.
- It should be verified with an interaction test that edits an entity and confirms whether the tree updates immediately without a full page refresh.

## Commands Run During Verification

```bash
php artisan test --compact tests/Feature/System/Products/ProductTreeViewTest.php
vendor/bin/pint --dirty --format agent
npm run build
rg -n "showPriceForm|savePrice|pricePlanId|editingPriceId|Price added|Price updated" resources/views -S
rg -n "On\\('requestAddPlan'\\)|On\\('requestAddLimit'\\)|On\\('requestAddFeature'\\)" app resources/views -S
```
