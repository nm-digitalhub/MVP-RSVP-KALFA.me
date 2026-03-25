# CLI Reference

Complete reference for Claude Code command-line interface.

## Basic Usage

```bash
# Start interactive session
claude

# Single prompt (headless mode)
claude -p "your prompt"

# With initial prompt
claude "explain this codebase"
```

## Common Flags

| Flag | Short | Description |
|------|-------|-------------|
| `--print` | `-p` | Headless mode - print response and exit |
| `--resume` | | Resume previous session |
| `--session-name` | | Name for the session |
| `--model` | `-m` | Model to use (sonnet, opus, haiku) |
| `--output-format` | | Output format (text, json, stream-json) |
| `--permission-mode` | | Permission handling mode |
| `--help` | `-h` | Show help |
| `--version` | `-v` | Show version |

## Session Management

### Start New Session

```bash
# Default (new session)
claude

# Named session
claude --session-name "feature-auth"
```

### Resume Session

```bash
# Resume most recent
claude --resume

# Resume by name
claude --resume "feature-auth"

# Resume by ID
claude --resume abc123
```

### List Sessions

```bash
claude sessions
```

## Model Selection

```bash
# Claude Sonnet (default)
claude

# Claude Opus (most capable)
claude --model opus

# Claude Haiku (fastest)
claude --model haiku
```

## Output Formats

### Text (Default)

```bash
claude -p "query" --output-format text
```

Plain text response.

### JSON

```bash
claude -p "query" --output-format json
```

```json
{
  "type": "result",
  "subtype": "success",
  "result": "response text",
  "cost_usd": 0.0123,
  "is_error": false,
  "session_id": "abc123"
}
```

### Stream JSON

```bash
claude -p "query" --output-format stream-json
```

Newline-delimited JSON events:
```json
{"type":"init","session_id":"abc123"}
{"type":"assistant","message":{"content":[{"type":"text","text":"..."}]}}
{"type":"result","result":"...","cost_usd":0.01}
```

## Permission Modes

| Mode | Flag | Behavior |
|------|------|----------|
| Default | `--permission-mode default` | Ask for edits and risky commands |
| Accept Edits | `--permission-mode acceptEdits` | Auto-approve file changes |
| Plan | `--permission-mode plan` | Read-only, no modifications |
| Bypass | `--permission-mode bypassPermissions` | Approve everything |

```bash
# Auto-approve edits
claude --permission-mode acceptEdits

# Read-only mode
claude --permission-mode plan
```

## System Prompts

### Replace System Prompt

```bash
claude --system-prompt "You are a Python expert. Only write Python code."
```

### Append to System Prompt

```bash
claude --append-system-prompt "Always add type hints to Python code."
```

## Agents Flag

Pass custom agents via CLI:

```bash
claude --agents '[{
  "name": "reviewer",
  "description": "Reviews code for issues",
  "tools": ["Read", "Glob", "Grep"],
  "permissionMode": "plan"
}]'
```

### Agent JSON Schema

```json
{
  "name": "string (required)",
  "description": "string (required)",
  "tools": ["array", "of", "tool", "names"],
  "disallowedTools": ["tools", "to", "block"],
  "permissionMode": "default|acceptEdits|plan|bypassPermissions",
  "model": "sonnet|opus|haiku"
}
```

## MCP Commands

### Add Server

```bash
# stdio transport
claude mcp add <name> --command "<cmd>" --args "<args>"

# HTTP transport
claude mcp add <name> --transport http --url "<url>"

# SSE transport
claude mcp add <name> --transport sse --url "<url>"

# With environment variable
claude mcp add <name> --command "cmd" --env KEY=value

# User scope (global)
claude mcp add -s user <name> --command "cmd"
```

### List Servers

```bash
claude mcp list
```

### Remove Server

```bash
claude mcp remove <name>
```

## Skill Commands

### Install Skill

```bash
# From URL
claude skill install https://example.com/skill.skill

# From GitHub
claude skill install github:user/repo/skill-name
```

### List Skills

```bash
claude skill list
```

## Configuration

### Open Config

```bash
claude config
```

Opens settings in editor.

### Config Locations

```
~/.claude/settings.json      # User settings
./.claude/settings.json      # Project settings
```

## Environment Variables

| Variable | Description |
|----------|-------------|
| `ANTHROPIC_API_KEY` | API key for authentication |
| `CLAUDE_MODEL` | Default model |
| `CLAUDE_CODE_DEBUG` | Enable debug logging |

## Interactive Commands

These work during a Claude Code session:

| Command | Description |
|---------|-------------|
| `/help` | Show available commands |
| `/compact` | Reduce context usage |
| `/clear` | Clear conversation |
| `/rewind` | Undo to checkpoint |
| `/status` | Show session status |
| `/config` | Open settings |
| `/model` | Switch model |
| `/plan` | Enter plan mode |

## Examples

### Quick Code Review

```bash
claude -p "Review the last commit for issues" --output-format text
```

### Generate Documentation

```bash
claude -p "Generate API docs for src/api/" > api-docs.md
```

### Batch Processing

```bash
for file in src/*.ts; do
  claude -p "Check $file for issues" --output-format json | jq -r '.result'
done
```

### CI Integration

```bash
# In CI script
claude -p "Review the diff: $(git diff main)" \
  --output-format json \
  --permission-mode plan | jq -r '.result'
```

### Named Session Workflow

```bash
# Start work
claude --session-name "auth-refactor"

# Later, resume
claude --resume "auth-refactor"
```

### With Custom Agent

```bash
claude --agents '[{"name":"security","description":"Security review","tools":["Read","Grep"],"permissionMode":"plan"}]' \
  -p "Use the security agent to audit src/auth/"
```

## Exit Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| 1 | General error |
| 2 | Invalid arguments |
| 130 | Interrupted (Ctrl+C) |

## Debugging

### Verbose Output

```bash
CLAUDE_CODE_DEBUG=1 claude
```

### Check Version

```bash
claude --version
```

### Test API Connection

```bash
claude -p "Hello" --output-format json | jq '.is_error'
```
