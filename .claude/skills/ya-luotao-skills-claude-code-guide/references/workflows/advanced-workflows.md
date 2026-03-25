# Advanced Workflows

Patterns for complex automation, parallel work, and CI/CD integration.

## Parallel Sessions

### Git Worktrees

Work on multiple branches simultaneously:

```bash
# Create worktrees for parallel work
git worktree add ../feature-auth -b feature/auth
git worktree add ../feature-api -b feature/api
git worktree add ../bugfix-login -b fix/login

# Terminal 1: Auth feature
cd ../feature-auth && claude

# Terminal 2: API feature
cd ../feature-api && claude

# Terminal 3: Bug fix
cd ../bugfix-login && claude
```

Each Claude session has independent context.

### When to Use Parallel Sessions

- Large features with independent parts
- Multiple bug fixes
- Exploring different approaches
- Review in one session, implement in another

## Headless Mode

### Basic Usage

```bash
# Single prompt, get response
claude -p "Explain the auth flow in this codebase"

# With output file
claude -p "Generate API documentation" > api-docs.md
```

### Output Formats

```bash
# Plain text (default)
claude -p "query" --output-format text

# JSON (structured)
claude -p "query" --output-format json

# Streaming JSON (for processing)
claude -p "query" --output-format stream-json
```

### JSON Output Structure

```json
{
  "type": "result",
  "subtype": "success",
  "result": "The response text...",
  "cost_usd": 0.0123,
  "is_error": false,
  "session_id": "abc123"
}
```

### Processing Streamed Output

```bash
claude -p "query" --output-format stream-json | while read -r line; do
  type=$(echo "$line" | jq -r '.type')
  if [ "$type" = "assistant" ]; then
    echo "$line" | jq -r '.message.content[0].text'
  fi
done
```

## Fan-Out Patterns

### Process Multiple Files

```bash
#!/bin/bash
# review-all.sh

for file in src/**/*.ts; do
  echo "Reviewing: $file"
  claude -p "Review $file for issues. Output only critical problems." \
    --output-format json | jq -r '.result'
done
```

### Parallel Processing

```bash
#!/bin/bash
# parallel-review.sh

find src -name "*.ts" | parallel -j4 \
  'claude -p "Review {} for security issues" --output-format json | jq -r ".result" > {}.review'
```

### Aggregate Results

```bash
#!/bin/bash
# aggregate-analysis.sh

# Collect analyses
for component in auth api database; do
  claude -p "Analyze the $component module" --output-format json \
    | jq -r '.result' > "analysis-$component.md"
done

# Combine
cat analysis-*.md > full-analysis.md
claude -p "Summarize these component analyses: $(cat full-analysis.md)"
```

## CI/CD Integration

### GitHub Actions

```yaml
# .github/workflows/claude-review.yml
name: Claude Code Review

on:
  pull_request:
    types: [opened, synchronize]

jobs:
  review:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Install Claude Code
        run: npm install -g @anthropic-ai/claude-code

      - name: Review Changes
        env:
          ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
        run: |
          git diff origin/main...HEAD > changes.diff
          claude -p "Review this diff for issues: $(cat changes.diff)" \
            --output-format json | jq -r '.result' > review.md

      - name: Post Review Comment
        uses: actions/github-script@v7
        with:
          script: |
            const fs = require('fs');
            const review = fs.readFileSync('review.md', 'utf8');
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: review
            });
```

### Pre-Commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

# Check staged files with Claude
STAGED=$(git diff --cached --name-only --diff-filter=ACM | grep -E '\.(ts|tsx|js|jsx)$')

if [ -n "$STAGED" ]; then
  echo "Running Claude check on staged files..."

  for file in $STAGED; do
    result=$(claude -p "Quick check $file for obvious issues. Reply 'OK' if fine, or list issues." \
      --output-format json 2>/dev/null | jq -r '.result')

    if [[ "$result" != *"OK"* ]]; then
      echo "Issues in $file:"
      echo "$result"
      exit 1
    fi
  done
fi
```

### Automated Documentation

```yaml
# .github/workflows/docs.yml
name: Generate Docs

on:
  push:
    branches: [main]
    paths: ['src/**']

jobs:
  docs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Generate API Docs
        env:
          ANTHROPIC_API_KEY: ${{ secrets.ANTHROPIC_API_KEY }}
        run: |
          claude -p "Generate API documentation from src/api/**" \
            --output-format text > docs/api.md

      - name: Commit Docs
        run: |
          git config user.name "Claude Bot"
          git config user.email "claude@example.com"
          git add docs/
          git diff --staged --quiet || git commit -m "docs: update API documentation"
          git push
```

## Writer/Reviewer Pattern

### Two-Agent Workflow

```bash
#!/bin/bash
# writer-reviewer.sh

TASK="Implement input validation for the signup form"

# Writer creates implementation
echo "Writer implementing..."
claude -p "$TASK" --output-format json > writer-output.json
RESULT=$(jq -r '.result' writer-output.json)

# Reviewer checks implementation
echo "Reviewer checking..."
claude -p "Review this implementation for issues: $RESULT" \
  --output-format json > reviewer-output.json

# Check for issues
ISSUES=$(jq -r '.result' reviewer-output.json)
if [[ "$ISSUES" == *"issue"* ]] || [[ "$ISSUES" == *"problem"* ]]; then
  echo "Issues found, requesting fixes..."
  claude -p "Address these review comments: $ISSUES"
fi
```

### Code Generation Pipeline

```bash
#!/bin/bash
# generate-with-review.sh

SPEC="$1"

# Generate code
claude -p "Generate code for: $SPEC" --output-format json | \
  jq -r '.result' > generated.ts

# Review generated code
claude -p "Review generated.ts for bugs and security issues" \
  --output-format json | jq -r '.result' > review.txt

# Generate tests
claude -p "Write tests for generated.ts" --output-format json | \
  jq -r '.result' > generated.test.ts

echo "Generated: generated.ts"
echo "Review: review.txt"
echo "Tests: generated.test.ts"
```

## Safe Autonomous Mode

### With Permission Bypass

```bash
# Full autonomy (use carefully!)
claude --permission-mode bypassPermissions -p "Refactor the auth module"
```

### With Accept Edits Only

```bash
# Auto-approve edits, but ask for commands
claude --permission-mode acceptEdits -p "Update all imports to use new path"
```

### Sandboxed Execution

```bash
# In a container
docker run -v $(pwd):/workspace -w /workspace node:18 \
  npx @anthropic-ai/claude-code -p "Run the test suite" \
  --permission-mode bypassPermissions
```

## Session Management

### Named Sessions

```bash
# Start named session
claude --session-name "auth-refactor"

# Resume by name
claude --resume "auth-refactor"
```

### Session Organization

```bash
# List sessions
claude sessions

# Resume most recent
claude --resume

# Continue specific session
claude --resume abc123
```

## Multi-Model Strategies

### Use Appropriate Models

```bash
# Quick questions - use haiku
claude -p "What's the syntax for..." --model haiku

# Complex analysis - use opus
claude -p "Design the architecture for..." --model opus

# Standard work - use sonnet (default)
claude -p "Implement the feature..."
```

### In Configuration

```json
{
  "hooks": {
    "PreToolUse": [{
      "matcher": "WebSearch",
      "command": "echo 'Using fast model for search'"
    }]
  }
}
```

## Integration Patterns

### With Editors

```bash
# VS Code task
{
  "label": "Claude: Explain Selection",
  "type": "shell",
  "command": "claude -p 'Explain this code: ${selectedText}'"
}
```

### With Make

```makefile
# Makefile
.PHONY: claude-review

claude-review:
	@claude -p "Review the last commit" --output-format text
```

### With npm Scripts

```json
{
  "scripts": {
    "claude:review": "claude -p 'Review staged changes'",
    "claude:docs": "claude -p 'Update documentation for changed files'",
    "claude:test": "claude -p 'Suggest tests for uncovered code'"
  }
}
```

## Error Recovery

### Retry Logic

```bash
#!/bin/bash
# with-retry.sh

MAX_RETRIES=3
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
  result=$(claude -p "$1" --output-format json 2>&1)

  if echo "$result" | jq -e '.is_error == false' > /dev/null 2>&1; then
    echo "$result" | jq -r '.result'
    exit 0
  fi

  RETRY_COUNT=$((RETRY_COUNT + 1))
  echo "Retry $RETRY_COUNT/$MAX_RETRIES..."
  sleep 2
done

echo "Failed after $MAX_RETRIES retries"
exit 1
```

### Graceful Degradation

```bash
#!/bin/bash
# safe-claude.sh

# Try Claude, fall back to manual
if ! claude -p "$1" --output-format text 2>/dev/null; then
  echo "Claude unavailable. Please review manually:"
  echo "$1"
fi
```

## Performance Tips

### Reduce Context

```bash
# Specific file instead of exploration
claude -p "Fix the bug in src/specific/file.ts"

# Clear scope
claude -p "Only look at the auth/ directory"
```

### Batch Operations

```bash
# Instead of many small calls
for file in *.ts; do
  claude -p "Check $file"
done

# One comprehensive call
claude -p "Check all TypeScript files in src/"
```

### Use Appropriate Output

```bash
# When you only need text
claude -p "query" --output-format text

# When you need to parse
claude -p "query" --output-format json
```
