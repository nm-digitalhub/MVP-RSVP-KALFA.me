# Common Workflows

Practical patterns for everyday tasks with Claude Code.

## Codebase Exploration

### Understanding a New Codebase

```
Explore the codebase structure and explain how it's organized.
```

Claude will:
1. List directories and key files
2. Identify frameworks and patterns
3. Map the architecture

### Finding Specific Functionality

```
Find where user authentication is handled.
```

```
How does the payment processing work? Trace the flow from
API request to database update.
```

### Understanding Dependencies

```
What external services does this project depend on?
Show me the integration points.
```

## Bug Fixing

### Standard Bug Fix Flow

```
The login form shows "undefined" when validation fails.
Fix the bug and verify with tests.
```

Claude's approach:
1. **Investigate**: Find relevant code, understand the issue
2. **Identify root cause**: Trace the error source
3. **Fix**: Make targeted changes
4. **Verify**: Run tests, check behavior

### Debugging with Error Messages

```
I'm getting this error:
TypeError: Cannot read property 'map' of undefined
  at UserList (src/components/UserList.tsx:15)

Fix it.
```

Provide:
- Error message
- Stack trace
- Steps to reproduce (if known)

### Investigating Intermittent Issues

```
The API sometimes returns 500 errors. Investigate the /users
endpoint and identify potential causes.
```

## Refactoring

### Safe Refactoring

```
Refactor the UserService to use dependency injection.
Run tests after each change to ensure nothing breaks.
```

Claude will:
1. Understand current implementation
2. Plan the refactoring
3. Make incremental changes
4. Verify tests pass after each step

### Extracting Components

```
The Dashboard component is too large. Extract the chart
section into its own component.
```

### Renaming Across Codebase

```
Rename the "UserManager" class to "UserService" across
the entire codebase.
```

## Test Writing

### Adding Tests for Existing Code

```
Add unit tests for src/utils/validation.ts.
Cover edge cases and error conditions.
```

### Test-Driven Bug Fix

```
Write a failing test that reproduces this bug:
[describe the bug]

Then fix it and verify the test passes.
```

### Coverage Improvement

```
Find untested code paths in the auth module and add tests.
```

## Git Workflows

### Creating Commits

```
/commit
```

Or with message:
```
/commit Add input validation to signup form
```

Claude will:
1. Check `git status` and `git diff`
2. Generate conventional commit message
3. Execute the commit

### Pull Request Creation

```
/commit-push-pr
```

Creates commit, pushes branch, opens PR with description.

### Reviewing Changes

```
Review my staged changes and suggest improvements.
```

```
What would you change about the code in this diff?
git diff HEAD~3
```

## Plan Mode

### Complex Changes

```
I need to add WebSocket support for real-time notifications.
Let's plan this first.
```

Claude enters plan mode:
1. Explores relevant code
2. Designs implementation approach
3. Presents plan for approval
4. Implements after approval

### Entering Plan Mode

```
/plan Add OAuth2 support with Google and GitHub providers
```

Or naturally:
```
Let's plan how to refactor the database layer before making changes.
```

## Working with Images

### Pasting Screenshots

1. Take screenshot
2. Paste directly into Claude Code (Cmd+V / Ctrl+V)
3. Ask about it:

```
[paste screenshot]
Implement this UI design.
```

### Referencing Image Files

```
Look at the mockup in designs/new-dashboard.png and
implement the layout.
```

### Error Screenshots

```
[paste screenshot of error]
What's causing this error and how do I fix it?
```

## File References

### Using @file Syntax

```
Review @src/auth/login.ts for security issues.
```

```
Based on @docs/api-spec.md, implement the missing endpoints.
```

### Multiple Files

```
Compare @src/old-utils.ts and @src/new-utils.ts.
What functionality is missing in the new version?
```

## Extended Thinking

### Complex Problem Solving

```
/think deeply about the best architecture for handling
real-time sync between mobile and web clients.
```

Extended thinking mode for:
- Architecture decisions
- Complex debugging
- Performance optimization
- Security analysis

## Code Generation

### From Description

```
Create a React hook that handles form validation with
these requirements:
- Email format validation
- Password strength (8+ chars, number, symbol)
- Async username availability check
- Debounced validation
```

### From Examples

```
Here's an example API route:
@src/api/users.ts

Create similar routes for posts and comments.
```

### Following Patterns

```
Create a new service following the same pattern as UserService.
This one should handle products.
```

## Documentation

### Generate Documentation

```
Add JSDoc comments to the public functions in src/utils/.
```

### Explain Code

```
Explain how the caching layer works. Include a diagram
if it helps.
```

### Update Documentation

```
Update the README to reflect the new CLI options we added.
```

## API Work

### Exploring APIs

```
Fetch the GitHub API docs and show me how to create issues
programmatically.
```

### Implementing Integrations

```
Add Stripe webhook handling for subscription events.
Use the patterns from our existing webhook handlers.
```

## Database Work

### Schema Changes

```
Add a "preferences" JSON column to the users table.
Create and run the migration.
```

### Query Optimization

```
The user listing query is slow. Analyze and optimize it.
```

## Environment Setup

### Project Setup

```
Help me set up this project. What do I need to install
and configure?
```

### Configuration

```
Configure ESLint and Prettier with the team's standard
rules from @.eslintrc.example.
```

## Best Practices for Workflows

### 1. Provide Verification Criteria

```
# Good
Add rate limiting. Run the load test to verify: npm run test:load

# Less good
Add rate limiting.
```

### 2. Be Specific About Scope

```
# Good
Fix the null pointer in UserList.tsx line 42.

# Too broad
Fix the bugs.
```

### 3. Reference Existing Patterns

```
# Good
Add error handling like we have in ApiClient.ts.

# Vague
Add proper error handling.
```

### 4. Iterate Incrementally

```
# Step 1
Set up the basic endpoint structure.

# Step 2 (after verifying step 1)
Add validation and error handling.

# Step 3
Add tests.
```

### 5. Use Plan Mode for Big Changes

```
# For complex changes
/plan Migrate from REST to GraphQL

# For small changes - just do it
Fix the typo in the error message.
```
