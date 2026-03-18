---
name: laravel-policy-reviewer
description: "Reviews and audits Laravel authorization policies in this multi-tenant SaaS. Activates when adding or modifying Policies, debugging 403 errors, verifying organization membership checks, auditing system admin / impersonation bypass logic, checking OrganizationUserRole-based access, or ensuring policy correctness across Owner/Admin/Editor/Viewer roles."
license: MIT
metadata:
  author: kalfa
---

# Laravel Policy Reviewer

## When to Apply

Activate this skill when:

- Adding a new Policy for a new model
- Debugging unexpected 403 or unauthorized access
- Verifying org membership scope in existing policies
- Adding role-based restrictions (Owner only, Admin+, etc.)
- Ensuring system admin impersonation bypass is present
- Reviewing `authorize()` calls in controllers or Livewire actions

## Multi-Tenant Policy Pattern

Every policy in this app must verify **organization membership**. The canonical pattern:

```php
// Correct org membership check:
$user->organizations()
    ->where('organizations.id', $resource->organization_id)
    ->exists();
```

### System Admin + Impersonation Bypass

All policies must bypass membership checks for system admins who are impersonating:

```php
public function view(User $user, SomeModel $model): bool
{
    // System admins impersonating always bypass
    if ($user->is_system_admin && session()->has('impersonation.original_organization_id')) {
        return true;
    }

    return $user->organizations()
        ->where('organizations.id', $model->organization_id)
        ->exists();
}
```

### Role-Based Checks

Use `OrganizationUserRole` enum via pivot:

```php
// app/Enums/OrganizationUserRole.php: Owner | Admin | Editor | Viewer

// Check if user is Owner or Admin in org:
private function isOwnerOrAdmin(User $user, Organization $org): bool
{
    $role = $user->organizations()
        ->where('organizations.id', $org->id)
        ->first()?->pivot?->role;

    return in_array($role, [
        OrganizationUserRole::Owner,
        OrganizationUserRole::Admin,
    ]);
}
```

## Policy Checklist

### Creating a New Policy

```bash
php artisan make:policy SomeModelPolicy --model=SomeModel
```

Verify it includes:

- [ ] `declare(strict_types=1);`
- [ ] Org membership check on every method
- [ ] Impersonation bypass for `is_system_admin`
- [ ] Role check for write operations (if needed)
- [ ] Registered in `AuthServiceProvider` (or auto-discovered via naming convention)

### Existing Policy Audit

```php
// tinker: test a policy manually
$user = User::find($userId);
$resource = SomeModel::find($resourceId);

echo $user->can('view', $resource) ? 'allowed' : 'denied';
echo $user->can('update', $resource) ? 'allowed' : 'denied';
```

## Policies in This App

| Policy | Model | Key Checks |
|---|---|---|
| `EventPolicy` | `Event` | Org membership |
| `OrganizationPolicy` | `Organization` | Owner/Admin for billing; any member for view |
| `GuestPolicy` | `Guest` | Org membership via event |
| `InvitationPolicy` | `Invitation` | Org membership via event |

## Controller Authorization

Always use `$this->authorize()` in controllers, not manual checks:

```php
// In API controllers:
$this->authorize('update', $event);

// In Livewire actions:
$this->authorize('update', $organization);
```

## Common Mistakes

| Mistake | Consequence | Fix |
|---|---|---|
| Missing impersonation bypass | System admin blocked while helping tenant | Add `is_system_admin + session check` |
| Using `request()->user()` instead of `$user` param | Wrong user in some contexts | Always use `$user` parameter |
| Checking `organization_id` from request | Tenant poisoning risk | Always resolve from `OrganizationContext::current()` |
| Missing `authorize()` in Livewire action | Unauthenticated users can call actions | Add `$this->authorize()` at start of action |
| Registering policy with wrong model class | Policy never invoked | Check `AuthServiceProvider` bindings |

## Org Membership — N+1 Awareness

The standard check `$user->organizations()->where(...)->exists()` hits the DB each call. For lists, use eager loading or `withExists`:

```php
// Efficient for admin lists:
Organization::withExists([
    'users as current_user_is_member' => fn($q) => $q->where('user_id', auth()->id())
])->get();
```

## Key Files

| File | Purpose |
|---|---|
| `app/Policies/` | All policy classes |
| `app/Enums/OrganizationUserRole.php` | Owner, Admin, Editor, Viewer |
| `app/Models/User.php` | `organizations()` relation with pivot role |
| `app/Models/Organization.php` | The tenant root |
| `app/Services/OrganizationContext.php` | `current()` — source of truth for active org |
| `bootstrap/app.php` | Gate/policy registration (Laravel 12, no Kernel) |
