# Skills System Reference

Skills are packaged prompts, workflows, and context that extend Claude Code's capabilities. They can be invoked manually or triggered automatically.

## What Are Skills?

Skills provide:
- **Reusable workflows** (commit, review, deploy)
- **Specialized knowledge** (framework guides, API references)
- **Templates** (code scaffolding, configurations)
- **Custom behaviors** (analysis, transformation)

## Skill Structure

A skill is a directory containing:

```
my-skill/
├── SKILL.md           # Required: main skill file
└── references/        # Optional: supporting files
    ├── guide.md
    └── examples/
        └── template.ts
```

### SKILL.md Format

```markdown
---
name: my-skill
description: |
  Brief description of what this skill does.
  When should Claude use this skill.
---

# Skill Title

Instructions and content for the skill.
```

## Frontmatter Fields

### Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Skill identifier (kebab-case) |
| `description` | string | When and how to use this skill |

### Optional Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `argument-hint` | string | - | Placeholder hint for arguments |
| `user-invocable` | boolean | false | User can trigger with `/name` |
| `disable-model-invocation` | boolean | false | Only manual trigger, no auto-invoke |
| `allowed-tools` | array | all | Tools the skill can use |
| `model` | string | - | Model override (sonnet, opus, haiku) |
| `context` | string | - | Context handling: "fork" for subagent |
| `agent` | string | - | Agent file to use |
| `hooks` | object | - | Skill-specific hooks |

### Example: User-Invocable Skill

```markdown
---
name: commit
description: Create a git commit with conventional message format
user-invocable: true
argument-hint: optional commit message
---

# Git Commit

Create a commit following conventional commits format.

1. Run `git status` to see changes
2. Run `git diff --staged` to review staged changes
3. Generate commit message: type(scope): description
4. Execute `git commit -m "message"`
```

### Example: Reference Skill

```markdown
---
name: react-patterns
description: |
  Use when working with React components.
  Provides patterns for hooks, state, and component design.
disable-model-invocation: true
---

# React Patterns Guide

## Component Structure
...

## Hook Patterns
...
```

## String Substitutions

Skills support argument substitution:

| Variable | Description |
|----------|-------------|
| `$ARGUMENTS` | All arguments as string |
| `$0` | First argument |
| `$1`, `$2`... | Subsequent arguments |

### Example

```markdown
---
name: analyze
description: Analyze a file for issues
user-invocable: true
argument-hint: <file-path>
---

Analyze the file at `$ARGUMENTS` for:
- Code quality issues
- Security vulnerabilities
- Performance concerns
```

Usage: `/analyze src/auth/login.ts`

## Dynamic Context

Include command output in skill content:

```markdown
---
name: git-status
description: Show git status with context
user-invocable: true
---

Current repository state:

`!git status`

Recent commits:

`!git log --oneline -5`
```

The `!command` syntax executes and embeds the output.

## Supporting Files

### References Directory

```
my-skill/
├── SKILL.md
└── references/
    ├── patterns.md      # Additional documentation
    ├── templates/       # Code templates
    │   └── component.tsx
    └── examples/        # Example code
        └── usage.ts
```

Reference files with `@path`:

```markdown
# In SKILL.md

## Patterns
See @references/patterns.md for detailed patterns.

## Template
Use this component template:
@references/templates/component.tsx
```

## Skill Locations

### Project Skills

```
your-project/.claude/skills/
└── my-skill/
    └── SKILL.md
```

Available only in this project.

### User Skills

```
~/.claude/skills/
└── my-skill/
    └── SKILL.md
```

Available in all projects.

### Installed Skills

```bash
# Install from URL
claude skill install https://example.com/skill.skill

# Install from GitHub
claude skill install github:user/repo/skill-name
```

## Running Skills

### User Invocation

```
/skill-name arguments
```

Requires `user-invocable: true` in frontmatter.

### Model Invocation

Claude automatically invokes when:
- Task matches skill description
- Skill not disabled for model invocation

### In Subagent

```markdown
---
name: review
description: Code review skill
context: fork
---
```

`context: fork` runs the skill in an isolated subagent.

## Tool Restrictions

Limit available tools:

```markdown
---
name: readonly-analysis
description: Analyze code without modifications
allowed-tools:
  - Read
  - Glob
  - Grep
---
```

## Model Selection

Override the model:

```markdown
---
name: quick-format
description: Simple formatting task
model: haiku
---
```

Options: `sonnet`, `opus`, `haiku`

## Skill Hooks

Add hooks specific to a skill:

```markdown
---
name: deploy
description: Deploy to production
hooks:
  PreToolUse:
    - matcher: Bash
      command: echo "Tool: $TOOL_NAME" >> deploy.log
---
```

## Examples

### Commit Skill

```markdown
---
name: commit
description: Create a conventional commit
user-invocable: true
argument-hint: optional message
---

# Create Commit

1. Check `git status` for changes
2. Review `git diff --staged`
3. Generate conventional commit message:
   - feat: new feature
   - fix: bug fix
   - docs: documentation
   - refactor: code restructure
4. Run `git commit -m "type(scope): message"`

If message provided: $ARGUMENTS
```

### Code Review Skill

```markdown
---
name: review
description: Review code for issues
user-invocable: true
allowed-tools:
  - Read
  - Glob
  - Grep
---

# Code Review

Review the current changes:

`!git diff --staged`

Check for:
1. Logic errors
2. Security issues
3. Performance problems
4. Code style violations
5. Missing tests

Provide actionable feedback.
```

### Framework Guide Skill

```markdown
---
name: nextjs-guide
description: |
  Use when working with Next.js App Router.
  Provides patterns and best practices.
---

# Next.js App Router Guide

## File Conventions
- `page.tsx` - Route page
- `layout.tsx` - Shared layout
- `loading.tsx` - Loading state
- `error.tsx` - Error boundary

## Data Fetching
- Server Components fetch directly
- Use `fetch` with caching options
- `revalidate` for ISR

## Patterns
@references/patterns.md
```

## Best Practices

### 1. Clear Descriptions

```markdown
# Good
description: |
  Use when creating React components.
  Provides component templates and patterns.

# Vague
description: React stuff
```

### 2. Focused Scope

One skill = one purpose. Split complex workflows:

```
# Instead of one mega-skill
deploy-skill (does everything)

# Multiple focused skills
build-skill
test-skill
deploy-skill
```

### 3. Progressive Detail

Main instructions in SKILL.md, details in references:

```markdown
# SKILL.md - Brief instructions
See @references/detailed-guide.md for edge cases.
```

### 4. Test Your Skills

```bash
# Test user invocation
/my-skill test-argument

# Verify behavior matches description
```

## Debugging Skills

### Check Loading

```
What skills are available?
```

### Verify Content

```
Show me the contents of the commit skill.
```

### Check Invocation

```
Why wasn't the react-patterns skill used?
```
