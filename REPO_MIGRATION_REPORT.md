# Repository Migration Report

**Date:** 2025-03-03  
**Project path:** `/var/www/vhosts/kalfa.me/httpdocs`

---

## 1. Previous remote

- **URL:** `https://github.com/pixelworxio/livewire-workflows-testbench.git`
- **Purpose:** Livewire Workflows testbench repository (wrong project).

---

## 2. New remote

- **URL:** `git@github.com:nm-digitalhub/MVP-RSVP-KALFA.me.git`
- **Purpose:** MVP RSVP KALFA.me application repository.

---

## 3. History: reset (clean reinitialization)

- **Decision:** Repository history did **not** belong to KALFA. Commits referred to testbench (e.g. “Add four production-ready workflow examples”, “Add comprehensive CLAUDE.md documentation for testbench repository”, “Initial commit”). The working tree was the MVP RSVP app; the Git history was from the wrong repo.
- **Action:** Old history was **not** preserved. The repo was reinitialized from scratch:
  - `.git` was removed.
  - `git init` was run.
  - Branch was set to `main`.
  - New remote `origin` was set to `git@github.com:nm-digitalhub/MVP-RSVP-KALFA.me.git`.
  - All current files were added and committed as a single initial commit.
- **Backup:** `.env` and `.env.testing` were copied to `/var/www/vhosts/kalfa.me/.env.backup.pre-migration` and `.env.testing.backup.pre-migration` before reinitialization.

---

## 4. Current branch

- **Branch:** `main`
- **Tracking:** `origin/main`

---

## 5. Last commit hash

- **Hash:** `fc9d206`
- **Message:** `Initial commit – MVP RSVP KALFA`

---

## 6. Verification

- **Remote:** `git remote -v` shows `origin` → `git@github.com:nm-digitalhub/MVP-RSVP-KALFA.me.git` (fetch and push).
- **SSH:** `ssh -T git@github.com` authenticates as `nm-digitalhub`.
- **Push:** `git push -u origin main` completed successfully; no push was made to the previous remote.
