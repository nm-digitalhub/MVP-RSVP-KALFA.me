# Sub-Agents Reference

Sub-agents are specialized Claude instances that handle specific tasks with isolated context and customized capabilities.

## What Are Sub-Agents?

Sub-agents allow:
- **Delegation**: Hand off complex investigations to specialists
- **Isolation**: Run tasks without polluting main context
- **Specialization**: Custom tools, permissions, and behaviors
- **Parallelism**: Multiple agents working simultaneously

## Built-in Agents

| Agent | Purpose | Tools |
|-------|---------|-------|
| **Explore** | Codebase exploration, finding files | Read, Glob, Grep |
| **Plan** | Architecture planning, design | Read, Glob, Grep |
| **general-purpose** | Multi-step tasks, research | All |
| **Bash** | Command execution | Bash |

### Using Built-in Agents

```
# Explore agent
Explore the authentication system and document how login works.

# Plan agent
Plan the implementation for adding OAuth support.

# General-purpose
Research how other projects implement rate limiting.
```

## Custom Agent Files

### Location

```
your-project/.claude/agents/    # Project agents
~/.claude/agents/               # User agents
```

### File Structure

```yaml
# .claude/agents/code-reviewer.md
---
name: code-reviewer
description: Reviews code for issues and improvements
tools:
  - Read
  - Glob
  - Grep
---

# Code Reviewer

Review code changes for:
1. Logic errors and bugs
2. Security vulnerabilities
3. Performance issues
4. Code style violations
5. Missing tests

Provide specific, actionable feedback.
```

## Frontmatter Fields

### Required

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Agent identifier |
| `description` | string | When to use this agent |

### Optional

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `tools` | array | all | Allowed tools |
| `disallowedTools` | array | [] | Blocked tools |
| `model` | string | parent | Model override |
| `permissionMode` | string | default | Permission handling |
| `skills` | array | [] | Preloaded skills |
| `hooks` | object | {} | Agent-specific hooks |

## Tool Configuration

### Allow Specific Tools

```yaml
---
name: readonly-analyst
tools:
  - Read
  - Glob
  - Grep
  - WebSearch
---
```

### Block Specific Tools

```yaml
---
name: no-web-agent
disallowedTools:
  - WebFetch
  - WebSearch
---
```

## Permission Modes

| Mode | Behavior |
|------|----------|
| `default` | Normal permission flow |
| `acceptEdits` | Auto-approve file edits |
| `plan` | Read-only, no modifications |
| `bypassPermissions` | Approve everything (careful!) |

```yaml
---
name: planner
permissionMode: plan
---

# Planning Agent
Research and design only, no modifications.
```

## Preloading Skills

Load skills into agent context:

```yaml
---
name: react-developer
skills:
  - react-patterns
  - typescript-guide
---

# React Developer
Builds React components following loaded patterns.
```

## Agent-Specific Hooks

```yaml
---
name: logged-agent
hooks:
  PostToolUse:
    - command: echo "$(date): $TOOL_NAME" >> agent.log
---
```

## Running Sub-Agents

### Foreground (Blocking)

```
Use the code-reviewer agent to review the auth changes.
```

Waits for agent to complete, returns results.

### Background

```
In the background, use the explore agent to document the API structure.
```

Returns immediately with task ID:
```
Started background agent (task_id: abc123)
Use "check task abc123" to see results.
```

### Checking Background Tasks

```
Check the status of task abc123.
```

## Resuming Agents

Agents maintain state and can be resumed:

```
Resume the code-reviewer agent to check the fixes.
```

The agent continues with its previous context.

## Examples

### Code Reviewer

```yaml
# .claude/agents/code-reviewer.md
---
name: code-reviewer
description: Reviews code for bugs, security, and style issues
tools:
  - Read
  - Glob
  - Grep
permissionMode: plan
---

# Code Review Agent

When reviewing code:

1. **Read the changes**: Understand what was modified
2. **Check for bugs**: Logic errors, edge cases, null handling
3. **Security review**: Injection, auth, data exposure
4. **Performance**: N+1 queries, unnecessary computation
5. **Style**: Naming, structure, consistency

Output format:
- 🔴 Critical: Must fix before merge
- 🟡 Warning: Should address
- 🔵 Suggestion: Consider improving
- ✅ Good: Positive observations
```

### Database Expert

```yaml
# .claude/agents/db-expert.md
---
name: db-expert
description: Database analysis and query optimization
tools:
  - Read
  - Glob
  - Grep
  - Bash
skills:
  - sql-patterns
---

# Database Expert

Specializes in:
- Schema analysis
- Query optimization
- Migration planning
- Index recommendations

For query analysis, explain:
1. Execution plan interpretation
2. Bottleneck identification
3. Optimization suggestions
```

### Security Auditor

```yaml
# .claude/agents/security-auditor.md
---
name: security-auditor
description: Security vulnerability assessment
tools:
  - Read
  - Glob
  - Grep
permissionMode: plan
---

# Security Audit Agent

Check for OWASP Top 10:
1. Injection (SQL, command, XSS)
2. Broken authentication
3. Sensitive data exposure
4. XXE
5. Broken access control
6. Security misconfiguration
7. XSS
8. Insecure deserialization
9. Vulnerable components
10. Insufficient logging

Report format:
- Severity (Critical/High/Medium/Low)
- Location (file:line)
- Description
- Remediation
```

### Test Writer

```yaml
# .claude/agents/test-writer.md
---
name: test-writer
description: Writes comprehensive tests
tools:
  - Read
  - Glob
  - Grep
  - Write
  - Bash
---

# Test Writer Agent

When writing tests:

1. Analyze the code to test
2. Identify test cases:
   - Happy path
   - Edge cases
   - Error conditions
   - Boundary values
3. Write tests following project patterns
4. Run tests to verify they pass

Use existing test files as style guide.
```

## CLI Agent Flag

Pass agents via CLI:

```bash
claude --agents '[{
  "name": "reviewer",
  "description": "Reviews code",
  "tools": ["Read", "Glob", "Grep"],
  "permissionMode": "plan"
}]'
```

## Best Practices

### 1. Single Responsibility

Each agent should do one thing well:

```yaml
# Good: Focused agent
name: sql-reviewer
description: Reviews SQL queries for issues

# Avoid: Kitchen sink
name: everything-agent
description: Does code review, testing, deployment, etc.
```

### 2. Minimal Permissions

Give agents only needed tools:

```yaml
# Good: Minimal tools for task
tools:
  - Read
  - Grep

# Avoid: All tools when not needed
tools:
  - Read
  - Write
  - Edit
  - Bash
  - WebFetch
```

### 3. Clear Instructions

Be explicit about what the agent should do:

```yaml
---
name: api-reviewer
---

# API Review

For each endpoint, check:
1. Authentication required?
2. Input validation present?
3. Error handling complete?
4. Rate limiting applied?
5. Response format consistent?

Output: Markdown table of findings
```

### 4. Use Background for Long Tasks

```
# Long exploration - run in background
In the background, use explore to map all API endpoints.

# Quick check - foreground
Use code-reviewer to check auth.ts
```

### 5. Document Custom Agents

In CLAUDE.md:
```markdown
## Custom Agents
- `code-reviewer`: Pre-merge code review
- `db-expert`: Database questions and optimization
- `security-auditor`: Security assessments
```
