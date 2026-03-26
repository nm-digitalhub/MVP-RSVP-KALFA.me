# Best Practices

Patterns and techniques for getting the most out of Claude Code.

## Effective Prompting

### Give Verification Criteria

Tell Claude how to verify success:

```
# Good - clear verification
Add input validation to the signup form.
Run `npm test` to verify nothing broke.

# Better - specific criteria
Add email and password validation:
- Email: valid format
- Password: 8+ chars, 1 number, 1 symbol
Run the validation tests: npm test -- --grep="validation"
```

### Be Specific About Scope

```
# Good - focused scope
Fix the null check in UserList.tsx line 42.

# Too broad - wastes context
Fix all the bugs in the codebase.

# Good - bounded exploration
Find all places where we call the payment API.
Focus on src/services/ and src/api/.
```

### Reference Existing Patterns

```
# Good - points to example
Add error handling like we have in src/lib/api-client.ts.

# Good - references structure
Create a new service following the UserService pattern.

# Vague
Add proper error handling.
```

### Provide Context When Needed

```
# Good - relevant context
The auth system uses JWT with refresh tokens stored in httpOnly cookies.
Add a token refresh endpoint.

# Good - shares constraints
We're using PostgreSQL 14. The query needs to work without
window functions (not supported in our version).
```

## Explore First, Then Code

### For Unfamiliar Code

```
# Step 1: Explore
Explain how the authentication flow works in this codebase.

# Step 2: Plan (if complex)
Now let's plan how to add OAuth support.

# Step 3: Implement
Implement the OAuth flow we discussed.
```

### For Bug Fixes

```
# Step 1: Investigate
The login is failing intermittently. Investigate possible causes.

# Step 2: Identify
Based on that, what's the most likely cause?

# Step 3: Fix
Fix the race condition in the session handler.
```

## CLAUDE.md Optimization

### What to Include

```markdown
# Good CLAUDE.md content

## Commands
- `npm run dev` - Start dev server
- `npm test` - Run tests

## Patterns
- Use functional components with hooks
- API calls go through src/lib/api-client.ts

## Structure
- src/features/ - Feature modules
- src/shared/ - Shared components
```

### What to Exclude

```markdown
# Avoid in CLAUDE.md

## JavaScript Basics  <-- Claude knows this
Variables are declared with let/const...

## What React Is  <-- Claude knows this
React is a library for building user interfaces...

## Obvious Patterns  <-- Claude will figure this out
Files ending in .ts are TypeScript...
```

### Keep It Concise

Every line of CLAUDE.md is loaded into every conversation. Optimize for density:

```markdown
# Concise
Run tests: `npm test`. Use Prisma for DB. Follow existing patterns.

# Verbose (wastes tokens)
When you want to run the tests, you should use the npm test
command which will execute our Jest test suite with all the
configured options that we have set up in the jest.config.js
file located in the root directory of the project...
```

## Context Management

### Monitor Usage

Watch for context warnings and compact proactively:

```
/compact
```

### Scope Your Requests

```
# Saves context
Read just the handleLogin function from auth.ts

# Uses more context
Read the entire auth.ts file
```

### Use Subagents for Exploration

```
Use the explore agent to find all database queries.
```

Subagents have isolated context - they don't fill up your main session.

### Reset When Appropriate

```
/clear
```

After completing a task, clear to start fresh.

## Course Correction

### Interrupt Early

If Claude is going the wrong direction, press `Esc` immediately:

```
[Claude starts modifying wrong file]
Esc
Stop - that's the wrong file. The login logic is in auth/login.ts, not user/login.ts.
```

### Use /rewind for Mistakes

```
/rewind
```

Restores to a previous checkpoint, undoing file changes.

### Clarify and Continue

```
That's not quite what I meant. I want the validation on the
client side, not the server. The server already validates.
```

## Delegation Strategies

### When to Use Subagents

| Task | Approach |
|------|----------|
| Quick fix | Main session |
| Code exploration | Explore subagent |
| Architecture planning | Plan subagent |
| Parallel investigations | Multiple subagents |
| Isolated experiments | Subagent with fork context |

### Effective Delegation

```
# Good - clear task for agent
Use the code-reviewer agent to check the auth/ directory
for security issues. Focus on:
- Input validation
- SQL injection
- Session handling

# Vague
Have an agent look at the code.
```

## Common Failure Patterns

### 1. Over-Engineering

**Symptom**: Claude adds unnecessary abstractions, features, or flexibility.

**Fix**: Be explicit about scope:
```
Just fix the bug. Don't refactor surrounding code or add
new features.
```

### 2. Wrong File Modifications

**Symptom**: Claude edits the wrong file or location.

**Fix**: Specify exactly:
```
Edit src/auth/login.ts (not src/user/login.ts).
The function is handleLogin around line 45.
```

### 3. Incomplete Testing

**Symptom**: Changes break existing functionality.

**Fix**: Require verification:
```
After each change, run `npm test` to ensure nothing broke.
```

### 4. Context Confusion

**Symptom**: Claude loses track of earlier decisions.

**Fix**: Summarize or use `/compact`:
```
/compact Focus on the auth refactoring we've been doing.
```

### 5. Permission Fatigue

**Symptom**: Too many permission prompts slow you down.

**Fix**: Use appropriate permission mode:
```bash
claude --permission-mode acceptEdits
```

Or approve command patterns in settings.

## Performance Tips

### Fast Feedback Loops

```
# Run tests frequently
Make the change, then run `npm test -- --watch` to verify.

# Use type checking
After editing, run `npm run typecheck`.
```

### Parallel Work

```bash
# Use worktrees for independent tasks
git worktree add ../feature-x -b feature/x
cd ../feature-x && claude
```

### Batch Similar Tasks

```
# Instead of one at a time
Update all the API error handlers to use the new ErrorResponse type.
Here are the files: src/api/users.ts, src/api/posts.ts, src/api/comments.ts.
```

## Security Considerations

### Review Before Committing

```
Show me the diff of all changes before we commit.
```

### Sensitive Files

Add to CLAUDE.md:
```markdown
## Security
Never modify .env files.
Don't commit secrets or credentials.
```

### Use Hooks for Protection

```json
{
  "hooks": {
    "PreToolUse": [{
      "matcher": "Edit",
      "command": "protect-secrets.sh"
    }]
  }
}
```

## Working with Tests

### Test-Driven Fixes

```
Write a failing test that reproduces the bug first.
Then fix the code to make it pass.
```

### Coverage Awareness

```
Check test coverage for the auth module.
Add tests for any uncovered paths.
```

### Regression Prevention

```
After fixing, add a regression test that would catch
this bug if it happens again.
```

## Documentation Habits

### Explain Complex Changes

```
After making the change, add a code comment explaining
why we need this workaround.
```

### Update Docs With Code

```
Update the README to reflect the new CLI options.
```

### Commit Messages

```
Write a detailed commit message explaining:
- What changed
- Why it was needed
- Any breaking changes
```

## When to Ask for Help

### Good Times to Pause

- Before making irreversible changes
- When multiple valid approaches exist
- When requirements are ambiguous
- Before running unfamiliar commands

### How to Ask

```
Before we proceed, I want to understand:
1. Should this be a breaking change or backwards compatible?
2. Do we need to support both old and new formats during migration?
```
