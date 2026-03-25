# Memory & CLAUDE.md

CLAUDE.md files provide persistent context that Claude Code reads at the start of every conversation. Use them to define project conventions, architecture, and workflows.

## Memory Hierarchy

Claude Code loads context from multiple sources in order:

| Level | Location | Scope | Purpose |
|-------|----------|-------|---------|
| **Managed** | `.claude/settings.local.json` | Session | Claude-managed memories |
| **Project** | `CLAUDE.md` | Repository | Project conventions |
| **Project Rules** | `.claude/rules/*.md` | Repository | Modular project rules |
| **User** | `~/.claude/CLAUDE.md` | All projects | Personal preferences |
| **User Rules** | `~/.claude/rules/*.md` | All projects | Reusable personal rules |
| **Local** | `CLAUDE.local.md` | Local only | Gitignored overrides |

**Loading order**: All levels are loaded and concatenated. Later entries don't override earlier ones - they add to the context.

## CLAUDE.md Location

### Project-Level (Recommended)

```
your-project/
├── CLAUDE.md              # Primary project context
├── .claude/
│   └── rules/
│       ├── coding.md      # Coding standards
│       ├── testing.md     # Test conventions
│       └── git.md         # Git workflow
└── src/
```

### User-Level

```
~/.claude/
├── CLAUDE.md              # Personal defaults
└── rules/
    ├── style.md           # Your coding style
    └── preferences.md     # Tool preferences
```

## CLAUDE.md Content

### Basic Structure

```markdown
# Project: MyApp

## Architecture
- Frontend: React 18 with TypeScript
- Backend: Node.js with Express
- Database: PostgreSQL with Prisma ORM

## Conventions
- Use functional components with hooks
- Prefer named exports over default exports
- All API routes return { data, error } shape

## Commands
- `npm run dev` - Start development server
- `npm test` - Run tests
- `npm run lint` - Lint code

## Important Files
- `src/lib/api.ts` - API client
- `src/hooks/` - Custom React hooks
- `prisma/schema.prisma` - Database schema
```

### What to Include

**Good content:**
- Project architecture overview
- Coding conventions specific to this project
- Important commands (dev, test, build)
- Key file locations
- External service configurations
- Team-specific workflows

**Avoid:**
- General programming knowledge (Claude knows this)
- Obvious information (file extensions, standard patterns)
- Lengthy documentation (link instead)
- Frequently changing information

### Keep It Concise

CLAUDE.md is loaded into every conversation. Every line consumes context tokens.

```markdown
# Good: Concise and specific
Use Prisma for database access. Run `npm test` after changes.

# Avoid: Verbose and obvious
When working with the database, you should use Prisma ORM
which is our chosen object-relational mapping tool for
interacting with the PostgreSQL database...
```

## Import Syntax

Use `@path` to import content from other files:

```markdown
# CLAUDE.md

## Project Overview
This is our main web application.

## Coding Standards
@.claude/rules/coding.md

## API Documentation
@docs/api-reference.md
```

**Import paths:**
- Relative to the file containing the import
- Can import any text file
- Recursive imports supported

## Rules Directory

### `.claude/rules/` Structure

Modular rules for organization:

```
.claude/rules/
├── coding.md          # Code style rules
├── testing.md         # Test requirements
├── security.md        # Security guidelines
└── api/
    └── conventions.md # API-specific rules
```

All files in `.claude/rules/` are automatically loaded.

### Path-Specific Rules

Use `paths:` frontmatter to apply rules only to certain files:

```markdown
---
paths:
  - src/components/**
  - src/ui/**
---

# Component Guidelines

- Use functional components
- Props interface named `{Component}Props`
- Export component and props type
```

**Glob patterns in paths:**
- `src/**` - All files in src recursively
- `*.test.ts` - All test files
- `src/api/**/*.ts` - TypeScript files in src/api

## User-Level Rules

### `~/.claude/CLAUDE.md`

Personal preferences applied to all projects:

```markdown
# Personal Preferences

- Prefer explicit types over inference
- Use early returns for guard clauses
- Write commit messages in imperative mood
```

### `~/.claude/rules/`

Reusable rules across projects:

```markdown
# ~/.claude/rules/typescript.md
---
paths:
  - "**/*.ts"
  - "**/*.tsx"
---

- Use strict TypeScript
- Prefer `type` over `interface` for simple types
- Avoid `any`, use `unknown` if needed
```

## Local Overrides

### CLAUDE.local.md

For machine-specific or personal overrides (gitignored):

```markdown
# CLAUDE.local.md

## Local Development
- My DB is on port 5433 (not default 5432)
- Use `npm run dev:local` for my setup

## Personal Notes
- Working on auth refactor in feature/auth branch
```

Add to `.gitignore`:
```
CLAUDE.local.md
```

## Managed Memories

Claude Code can save memories via `/remember`:

```
/remember Always run tests before committing
```

Saved to `.claude/settings.local.json`:
```json
{
  "memories": [
    "Always run tests before committing"
  ]
}
```

**Note:** These are session-managed and may be gitignored.

## Best Practices

### 1. Start Minimal

Begin with essentials, add as needed:

```markdown
# Initial CLAUDE.md
- Run `npm test` after changes
- Follow existing code patterns
```

### 2. Be Specific

```markdown
# Good
API handlers use zod for validation. See src/lib/validation.ts.

# Vague
We use validation in the API.
```

### 3. Update When Patterns Change

Keep CLAUDE.md current:
```markdown
# Outdated
Use class components for state

# Current
Use hooks for state management (useState, useReducer)
```

### 4. Link Instead of Embed

```markdown
# Good
See @docs/architecture.md for system design.

# Avoid
[500 lines of architecture documentation inline]
```

### 5. Organize by Purpose

```markdown
## Quick Reference
- Dev: `npm run dev`
- Test: `npm test`

## Architecture
- src/features/ - Feature modules
- src/shared/ - Shared utilities

## Conventions
- PascalCase for components
- camelCase for functions
```

## Debugging Memory Loading

### Check What's Loaded

Ask Claude Code:
```
What do you know about this project from CLAUDE.md?
```

### Verify Imports

```
Show me the contents of the loaded rules files.
```

### Check Precedence

If rules conflict, later loads add context (don't override). Be explicit about which rule applies:

```markdown
# In .claude/rules/exceptions.md
Exception: In test files, mocking is allowed despite the
"no magic values" rule in coding.md.
```

## Examples

### Web Application

```markdown
# CLAUDE.md - MyWebApp

## Stack
- Next.js 14 (App Router)
- TypeScript strict mode
- Tailwind CSS
- Prisma + PostgreSQL

## Commands
- `pnpm dev` - Development
- `pnpm test` - Tests
- `pnpm db:migrate` - Run migrations

## Patterns
- Server components by default
- 'use client' only when needed
- Colocate components with routes

## Key Files
- `app/` - Routes and pages
- `components/ui/` - Shared UI
- `lib/` - Utilities and clients
```

### API Service

```markdown
# CLAUDE.md - PaymentAPI

## Architecture
REST API with Express.js, TypeScript strict mode.

## Commands
- `npm run dev` - Watch mode
- `npm test` - Jest tests
- `npm run typecheck` - Type checking

## Conventions
- Controllers in `src/controllers/`
- Services in `src/services/`
- All endpoints return `{ data, error }` shape
- Use zod schemas for request validation

## Environment
- Copy `.env.example` to `.env`
- Requires STRIPE_KEY and DATABASE_URL
```
