# Hooks Configuration

Hooks execute shell commands at specific points in Claude Code's lifecycle, enabling automation, validation, and custom behaviors.

## Hook Events

```
┌─────────────────────────────────────────────────────────────────┐
│                     HOOK EVENT LIFECYCLE                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  SessionStart ─────────────────────────────────────────────────▶│
│       │                                                          │
│       ▼                                                          │
│  UserPromptSubmit ────────────────────────────────────────────▶ │
│       │                                                          │
│       ▼                                                          │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  Tool Execution Loop                                     │    │
│  │                                                          │    │
│  │  PreToolUse ───▶ [Permission?] ───▶ PostToolUse         │    │
│  │       │              │                   │               │    │
│  │       │         PermissionRequest        │               │    │
│  │       │              │                   │               │    │
│  │       └──────────────┴───────────────────┘               │    │
│  │                      │                                    │    │
│  │                 (repeat)                                  │    │
│  └─────────────────────────────────────────────────────────┘    │
│       │                                                          │
│       ▼                                                          │
│  Stop ──────────────────────────────────────────────────────────▶│
│       │                                                          │
│       ▼                                                          │
│  SessionEnd ────────────────────────────────────────────────────▶│
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## Event Reference

| Event | Trigger | Use Cases |
|-------|---------|-----------|
| `SessionStart` | Session begins | Setup, logging, context injection |
| `UserPromptSubmit` | Before processing user input | Validation, preprocessing |
| `PreToolUse` | Before tool execution | Validation, approval, modification |
| `PermissionRequest` | Permission prompt shown | Auto-approval, logging |
| `PostToolUse` | After tool execution | Logging, formatting, cleanup |
| `Stop` | Claude finishes responding | Summary, notifications |
| `SubagentStart` | Subagent spawned | Logging, context setup |
| `SubagentStop` | Subagent completes | Result processing |
| `PreCompact` | Before context compaction | Save state, custom summary |
| `SessionEnd` | Session ends | Cleanup, final logging |
| `Notification` | Claude sends notification | Custom notification handling |

## Configuration

### Location

Hooks are configured in settings:

```
# User settings
~/.claude/settings.json

# Project settings
your-project/.claude/settings.json
```

### Basic Structure

```json
{
  "hooks": {
    "EventName": [
      {
        "command": "your-command",
        "matcher": "optional-pattern"
      }
    ]
  }
}
```

### Multiple Hooks

```json
{
  "hooks": {
    "PostToolUse": [
      { "command": "logger.sh" },
      { "command": "formatter.sh", "matcher": "Edit" }
    ]
  }
}
```

Hooks run in order. If one fails (non-zero exit), subsequent hooks still run.

## Matcher Patterns

Filter hooks to specific tools or patterns:

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Bash",
        "command": "validate-command.sh"
      },
      {
        "matcher": "Edit",
        "command": "check-file-protected.sh"
      }
    ]
  }
}
```

### Glob Patterns

```json
{
  "matcher": "*.ts",
  "command": "lint-typescript.sh"
}
```

## Hook Input

Hooks receive JSON input via stdin:

### SessionStart Input

```json
{
  "sessionId": "abc123",
  "workingDirectory": "/path/to/project"
}
```

### PreToolUse Input

```json
{
  "sessionId": "abc123",
  "tool": {
    "name": "Bash",
    "input": {
      "command": "npm test"
    }
  }
}
```

### PostToolUse Input

```json
{
  "sessionId": "abc123",
  "tool": {
    "name": "Edit",
    "input": {
      "file_path": "/path/to/file.ts",
      "old_string": "...",
      "new_string": "..."
    }
  },
  "result": "File edited successfully"
}
```

## Hook Output

### Exit Codes

| Exit Code | Meaning |
|-----------|---------|
| 0 | Success, continue normally |
| 2 | Block the action (PreToolUse only) |
| Other | Error, logged but continues |

### JSON Output

For richer control, output JSON to stdout:

```json
{
  "hookSpecificOutput": {
    "decision": "allow",
    "reason": "Approved by policy"
  }
}
```

### PreToolUse Decisions

```json
{
  "hookSpecificOutput": {
    "decision": "allow"
  }
}
```

| Decision | Effect |
|----------|--------|
| `allow` | Approve without user prompt |
| `deny` | Block with reason |
| `ask` | Show permission prompt (default) |

### With Message

```json
{
  "hookSpecificOutput": {
    "decision": "deny",
    "reason": "Modifying protected file"
  }
}
```

### Injecting Context

Add to conversation context:

```json
{
  "context": "Note: This file was last modified 2 hours ago."
}
```

## Environment Variables

Hooks have access to:

| Variable | Description |
|----------|-------------|
| `CLAUDE_SESSION_ID` | Current session ID |
| `CLAUDE_PROJECT_DIR` | Project root path |
| `CLAUDE_WORKING_DIR` | Current working directory |
| `CLAUDE_ENV_FILE` | Path to env file if set |
| `TOOL_NAME` | Tool being used (tool hooks) |

## Prompt-Based Hooks

Use Claude to process hook logic:

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "type": "prompt",
        "prompt": "Check if this command is safe: $COMMAND"
      }
    ]
  }
}
```

## Examples

### Command Logging

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Bash",
        "command": "echo \"$(date): $TOOL_INPUT\" >> ~/.claude/commands.log"
      }
    ]
  }
}
```

### File Protection

```bash
#!/bin/bash
# protect-files.sh

INPUT=$(cat)
FILE=$(echo "$INPUT" | jq -r '.tool.input.file_path // empty')

PROTECTED_PATTERNS=(
  "*.env"
  "*.secret"
  "**/credentials/*"
)

for pattern in "${PROTECTED_PATTERNS[@]}"; do
  if [[ "$FILE" == $pattern ]]; then
    echo '{"hookSpecificOutput":{"decision":"deny","reason":"Protected file"}}'
    exit 0
  fi
done

echo '{"hookSpecificOutput":{"decision":"ask"}}'
```

```json
{
  "hooks": {
    "PreToolUse": [
      {
        "matcher": "Edit",
        "command": "~/.claude/hooks/protect-files.sh"
      },
      {
        "matcher": "Write",
        "command": "~/.claude/hooks/protect-files.sh"
      }
    ]
  }
}
```

### Auto-Format on Edit

```json
{
  "hooks": {
    "PostToolUse": [
      {
        "matcher": "Edit",
        "command": "prettier --write \"$FILE_PATH\" 2>/dev/null || true"
      }
    ]
  }
}
```

### Session Logging

```json
{
  "hooks": {
    "SessionStart": [
      {
        "command": "echo \"Session started: $(date)\" >> ~/.claude/sessions.log"
      }
    ],
    "SessionEnd": [
      {
        "command": "echo \"Session ended: $(date)\" >> ~/.claude/sessions.log"
      }
    ]
  }
}
```

### Dangerous Command Warning

```bash
#!/bin/bash
# warn-dangerous.sh

INPUT=$(cat)
COMMAND=$(echo "$INPUT" | jq -r '.tool.input.command // empty')

DANGEROUS_PATTERNS=(
  "rm -rf"
  "git reset --hard"
  "git push --force"
  "DROP TABLE"
  "DELETE FROM"
)

for pattern in "${DANGEROUS_PATTERNS[@]}"; do
  if [[ "$COMMAND" == *"$pattern"* ]]; then
    echo "{\"context\":\"⚠️ WARNING: This command contains '$pattern'. Please confirm.\"}"
    exit 0
  fi
done
```

### Subagent Tracking

```json
{
  "hooks": {
    "SubagentStart": [
      {
        "command": "echo \"Subagent started: $(date)\" >> agents.log"
      }
    ],
    "SubagentStop": [
      {
        "command": "echo \"Subagent completed: $(date)\" >> agents.log"
      }
    ]
  }
}
```

### Context Injection

```bash
#!/bin/bash
# inject-context.sh

# Add project-specific context at session start
echo '{
  "context": "Current sprint: Authentication refactor\nPriority: Security fixes for login flow"
}'
```

```json
{
  "hooks": {
    "SessionStart": [
      {
        "command": "~/.claude/hooks/inject-context.sh"
      }
    ]
  }
}
```

## Best Practices

### 1. Fast Hooks

Hooks block execution. Keep them fast:

```bash
# Good: Quick check
grep -q "PROTECTED" "$FILE" && exit 2

# Avoid: Slow operations
npm run full-lint  # Too slow for hook
```

### 2. Fail Gracefully

```bash
# Good: Handle failures
prettier --write "$FILE" 2>/dev/null || true

# Risky: May break flow
prettier --write "$FILE"
```

### 3. Use Matchers

Only run hooks when relevant:

```json
{
  "matcher": "Edit",
  "command": "format.sh"
}
```

### 4. Log Errors

```bash
#!/bin/bash
if ! process_hook; then
  echo "Hook failed: $?" >> ~/.claude/hook-errors.log
fi
```

### 5. Test Hooks

```bash
# Test with sample input
echo '{"tool":{"name":"Bash","input":{"command":"ls"}}}' | ./your-hook.sh
```

## Debugging

### Enable Hook Logging

```json
{
  "hookDebug": true
}
```

### Check Hook Output

Hooks output goes to:
- stdout: Captured for JSON responses
- stderr: Logged for debugging

### Common Issues

| Issue | Cause | Fix |
|-------|-------|-----|
| Hook not running | Wrong event name | Check spelling |
| Hook blocking everything | Exit code 2 | Check exit logic |
| Slow responses | Slow hook | Optimize or make async |
| Missing env vars | Not exported | Check variable availability |
