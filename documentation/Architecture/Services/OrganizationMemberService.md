---
date: 2026-03-16
tags: [architecture, service, organization, members, invitations, roles]
status: active
---

# OrganizationMemberService

**File**: `app/Services/OrganizationMemberService.php`

## Purpose

Manages organization membership: sending email invitations, accepting invitations, direct-adding users, role updates, and member removal. Keeps Spatie permission roles in sync with the pivot-table role at all times.

---

## Methods

### `invite(organization, email, role)`

Sends an email invitation to a non-member.

```
invite(org, email@example.com, role: Admin)
    │
    ├── Delete any existing pending invitation for this email+org (replace semantics)
    │
    ├── OrganizationInvitation::create([
    │       token: Str::random(64),   ← 64-char secure random token
    │       role: {role},
    │       expires_at: now() + 7 days
    │   ])
    │
    └── Mail::to(email)->send(OrganizationInvitationMail)
        └── Invitation link: {app.url}/invitations/{token}/accept
```

**Security properties:**
- Token is 64 characters of cryptographic randomness
- Expires in 7 days (`expires_at`)
- Re-inviting replaces any pending invite for the same email (prevents duplicate tokens)

---

### `acceptInvitation(token, user)`

Called when the invited user clicks the link and is authenticated.

```
acceptInvitation(token, user)
    │
    ├── OrganizationInvitation WHERE token = ? → 404 if not found
    ├── [invitation.isExpired()] → Exception('This invitation has expired.')
    │
    └── DB::transaction:
        ├── addMember(invitation.organization, user, invitation.role)
        │   └── organization_users sync + Spatie role sync
        │
        ├── [user has no current_organization_id?]
        │   └── user.current_organization_id = invitation.organization_id
        │
        └── invitation.delete()  ← token consumed, one-time use
```

---

### `addMember(organization, user, role)`

Direct-add flow — used by system admins bypassing the invitation step.

```
DB::transaction:
    ├── organization.users()->syncWithoutDetaching([user.id → {role}])
    └── syncSpatieRole(organization, user, role)
```

---

### `removeMember(organization, user)`

Removes a member. Guards against removing the last owner.

```
removeMember(org, user)
    │
    ├── [user is Owner AND only owner in org?]
    │   └── Exception('Cannot remove the only owner')
    │
    └── DB::transaction:
        ├── organization.users()->detach(user.id)
        │
        ├── setPermissionsTeamId(org.id)
        │   └── user.syncRoles([])   ← clear all Spatie roles in this org
        │
        └── [user.current_organization_id == org.id?]
            └── user.current_organization_id = user.organizations().first()?.id
```

---

### `updateRole(organization, user, role)`

Updates the pivot role and re-syncs Spatie permissions.

```
DB::transaction:
    ├── organization_users.role = {new role}
    └── syncSpatieRole(organization, user, new role)
```

---

## Spatie Role Mapping

`syncSpatieRole()` maps KALFA roles to Spatie roles (team-scoped):

| KALFA Role | Spatie Role |
|-----------|------------|
| `Owner` | `Organization Admin` |
| `Admin` | `Organization Admin` |
| `Editor` (Member) | `Organization Editor` |

> **Note**: `OrganizationUserRole::Member` maps to `Organization Editor` in Spatie. The Spatie role is created if it doesn't exist (`findOrCreate`).

---

## Invitation Token Security

| Property | Value |
|---------|-------|
| Token length | 64 chars (cryptographically random) |
| Expiry | 7 days from creation |
| One-time use | Deleted on acceptance |
| Replacement | Re-inviting the same email deletes the old token |

---

## Related

- [[Architecture/Permissions]] — Role definitions and Spatie team-scoped permissions
- [[Architecture/Services/OrganizationContext]] — Org context switching after join
- [[Architecture/Auth]] — Authentication required before `acceptInvitation`
- [[Architecture/Glossary]] — OrganizationUser, OrganizationInvitation definitions
