# The Agentic Loop

Claude Code operates as an **agentic system** - it doesn't just respond to questions, it takes actions to accomplish goals. Understanding this loop helps you work with Claude Code effectively.

## The Three Phases

### Phase 1: Gather Context

Before taking action, Claude Code builds understanding:

```
READ FILES          → Understand code structure, dependencies
SEARCH CODE         → Find relevant functions, patterns
RUN COMMANDS        → Check git status, test state, environment
ANALYZE ERRORS      → Parse stack traces, logs, error messages
```

**What triggers context gathering:**
- Your initial prompt or follow-up question
- Errors from previous actions
- Incomplete information for the task

**Tools used:** `Read`, `Glob`, `Grep`, `Bash` (read-only commands)

### Phase 2: Take Action

Once context is sufficient, Claude Code acts:

```
EDIT FILES          → Modify source code, configs
CREATE FILES        → New components, tests, docs
RUN COMMANDS        → Build, test, git operations
MAKE DECISIONS      → Choose approaches, resolve ambiguity
```

**Permission handling:**
- **Auto-approved**: Safe read operations, approved command patterns
- **Requires approval**: File edits, new commands, destructive operations
- **Configurable**: Via `--permission-mode` flag or settings

### Phase 3: Verify Results

Claude Code checks its work:

```
RUN TESTS           → Verify functionality preserved
CHECK OUTPUT        → Parse command results for errors
RE-READ FILES       → Confirm edits applied correctly
VALIDATE STATE      → Ensure consistency
```

**Verification triggers:**
- After file edits (may re-read to confirm)
- After running build/test commands
- When you mention tests or validation criteria

## The Feedback Loop

```
┌──────────────────────────────────────────────────────────────┐
│                                                               │
│   ┌─────────┐    ┌─────────┐    ┌─────────┐                 │
│   │ GATHER  │───▶│  ACT    │───▶│ VERIFY  │──┐              │
│   └─────────┘    └─────────┘    └─────────┘  │              │
│        ▲                                      │              │
│        │         ┌────────────────────────────┘              │
│        │         │                                           │
│        │         ▼                                           │
│        │   ┌──────────┐      ┌──────────┐                   │
│        └───│ NEED MORE│      │ COMPLETE │───▶ Report        │
│            │ CONTEXT? │      │          │     Results        │
│            └──────────┘      └──────────┘                   │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

The loop continues until:
1. **Task complete**: All requirements met, verified
2. **Blocked**: Needs information only you can provide
3. **Error**: Unrecoverable error requiring intervention
4. **Permission denied**: You rejected a required action

## How Claude Chooses Tools

Claude Code selects tools based on:

| Signal | Tool Choice |
|--------|-------------|
| "Find all uses of X" | `Grep` for pattern search |
| "Read the config file" | `Read` for specific file |
| "What files are in src/" | `Glob` for file listing |
| "Run the tests" | `Bash` for command execution |
| "Edit the function" | `Edit` for modifications |
| File path provided | `Read` that specific file |
| Error message shown | `Read` + `Grep` to investigate |

## Permission Modes

| Mode | Behavior |
|------|----------|
| **default** | Ask for edits, some commands |
| **acceptEdits** | Auto-approve file edits |
| **plan** | Research only, no modifications |
| **bypassPermissions** | Trust all actions (use cautiously) |

Set via `--permission-mode` flag:
```bash
claude --permission-mode=acceptEdits
```

## Agentic Behaviors

### Autonomous Recovery
When an action fails, Claude Code attempts recovery:
```
Action: Run tests
Result: 3 tests failed
Response: Claude reads failing test files, identifies issues,
          proposes fixes, re-runs tests to verify
```

### Context Building
Claude Code may read files you didn't explicitly mention:
```
Request: "Fix the login bug"
Claude reads:
  - auth/login.ts (mentioned)
  - auth/types.ts (imported by login.ts)
  - tests/auth.test.ts (to understand expected behavior)
  - .env.example (to understand config)
```

### Tool Chaining
Complex tasks involve multiple tools in sequence:
```
1. Glob("**/*.test.ts") → Find test files
2. Grep("describe.*auth") → Find auth tests
3. Read(auth.test.ts) → Understand test structure
4. Edit(auth.test.ts) → Add new test
5. Bash("npm test") → Verify test passes
```

## Interrupting the Loop

You can intervene at any point:

| Action | Effect |
|--------|--------|
| `Esc` | Cancel current generation |
| Deny permission | Block action, Claude adjusts |
| New message | Redirect focus |
| `/clear` | Reset conversation |
| `/rewind` | Undo recent changes |
| `Ctrl+C` | Exit completely |

## Best Practices

### Give Verification Criteria
```
# Good - Claude knows how to verify
"Add input validation. Run `npm test` to verify."

# Less good - no verification path
"Add input validation."
```

### Let Claude Explore
```
# Good - allows context gathering
"Explore how errors are handled, then improve the API responses."

# Less good - assumes you know the structure
"Edit src/errors.ts to improve error handling."
```

### Provide Context When Needed
```
# Good - provides relevant context
"The auth uses JWT with refresh tokens. Add token refresh to the client."

# Less good - Claude must discover this
"Add token refresh to the client."
```

## Common Patterns

### Investigation First
```
User: "Why is the API slow?"
Claude:
  1. Reads API route handlers
  2. Finds database queries
  3. Checks for N+1 queries
  4. Runs profiling command
  5. Reports findings with recommendations
```

### Incremental Changes
```
User: "Refactor the user service"
Claude:
  1. Reads current implementation
  2. Proposes refactoring plan
  3. Makes changes in stages
  4. Runs tests after each stage
  5. Confirms all tests pass
```

### Error Recovery
```
User: "Deploy to staging"
Claude:
  1. Runs deploy command
  2. Deploy fails (missing env var)
  3. Reads error, identifies cause
  4. Suggests fix or asks for env var
  5. Retries after fix
```
