# Context Management

Claude Code has a limited context window. Understanding how to manage it helps you work effectively on longer tasks.

## Context Window Basics

| Aspect | Details |
|--------|---------|
| **Total size** | ~200K tokens |
| **What counts** | Conversation, file contents, command output, tool results |
| **Warning threshold** | Status shows when running low |
| **Automatic handling** | Compaction when approaching limit |

## What Consumes Context

### High Consumers

| Source | Impact | Mitigation |
|--------|--------|------------|
| Large file reads | High | Read specific sections |
| Long command output | High | Use head/tail, filter |
| Many small files | Medium-High | Be selective |
| MCP tool responses | Variable | Depends on tool |
| Conversation history | Accumulates | Use /compact |

### Efficient Patterns

```bash
# Instead of reading entire large file
Read entire 5000-line file  # Uses ~10K tokens

# Read specific section
Read lines 100-200 of file  # Uses ~200 tokens

# Instead of full command output
npm test                     # May output 1000s of lines

# Filter to relevant parts
npm test 2>&1 | head -50    # Just first 50 lines
```

## Automatic Compaction

When context approaches the limit, Claude Code automatically compacts:

1. **Summarizes** earlier conversation turns
2. **Preserves** recent messages and key context
3. **Maintains** file modification history
4. **Keeps** CLAUDE.md contents

**You'll see**: A message indicating compaction occurred

## Manual Compaction

### /compact Command

Use `/compact` to proactively reduce context:

```
/compact
```

**When to use:**
- Before starting a new subtask
- After completing a major milestone
- When context warning appears
- To improve response speed

### Custom Compact Instructions

You can customize what compaction preserves:

```
/compact focus on the authentication changes
```

This tells the compaction to prioritize auth-related context.

## Session Management

### Session Persistence

Claude Code automatically saves sessions:

```
~/.claude/sessions/
├── session-abc123.json      # Recent session
├── session-def456.json      # Older session
└── ...
```

### Resuming Sessions

```bash
# Resume most recent session
claude --resume

# Resume specific session
claude --resume session-abc123

# List available sessions
claude sessions
```

### Starting Fresh

```bash
# New session, no history
claude

# Clear current session
/clear
```

## Checkpoints and Rewind

### Automatic Checkpoints

Claude Code creates checkpoints at key moments:
- After significant file changes
- Before risky operations
- At natural task boundaries

### Using /rewind

```
/rewind
```

Shows available checkpoints and lets you restore to a previous state:

```
Available checkpoints:
1. Before editing auth.ts (2 min ago)
2. Before running migration (5 min ago)
3. After adding tests (10 min ago)

Select checkpoint to restore:
```

**What /rewind restores:**
- File system state (reverts changes)
- Conversation position
- Git state (if commits were made)

## Context-Aware Behaviors

### Intelligent File Reading

Claude Code reads files strategically:

```
# Full read for small files (<500 lines)
config.json → Reads entirely

# Partial read for large files
large-data.json → Reads sample + structure

# Targeted read based on task
"Fix the login function" → Reads login section specifically
```

### Output Truncation

Long command outputs are automatically truncated:

```
Command output (showing last 100 lines of 5000):
...
[truncated output]
```

## Optimization Strategies

### For Long Tasks

1. **Milestone compaction**: Run `/compact` after completing subtasks
2. **Focused prompts**: Be specific about what you need
3. **Verify incrementally**: Don't wait until the end to test

### For Large Codebases

1. **Guide exploration**: "Focus on the auth/ directory"
2. **Use patterns**: "Find where UserService is instantiated"
3. **Limit scope**: "Only look at TypeScript files"

### For Context-Heavy Tools

1. **MCP responses**: Some tools return large payloads
2. **Web fetches**: Pages can be large
3. **Database queries**: Limit result sets

## Monitoring Context

### /status Command

```
/status
```

Shows:
- Current context usage
- Session information
- Active MCP servers
- Recent checkpoints

### Visual Indicators

The terminal shows context status:
- **Green**: Plenty of context
- **Yellow**: Getting full
- **Red**: Near limit, compaction imminent

## CLAUDE.md and Context

CLAUDE.md files are always in context:

```
~/.claude/CLAUDE.md        # Always loaded
project/CLAUDE.md          # Always loaded for project
project/.claude/rules/*.md # All rules loaded
```

**Keep CLAUDE.md concise** - every line uses context in every message.

## Multi-Session Patterns

### Parallel Sessions

For large tasks, use multiple terminal sessions:

```bash
# Terminal 1: Working on frontend
cd frontend && claude

# Terminal 2: Working on backend
cd backend && claude
```

Each session has independent context.

### Worktree Pattern

For parallel changes:

```bash
git worktree add ../feature-auth -b feature/auth
git worktree add ../feature-api -b feature/api

# Terminal 1
cd ../feature-auth && claude

# Terminal 2
cd ../feature-api && claude
```

## Troubleshooting

### "Context limit approaching"

1. Run `/compact`
2. Start `/clear` if task is done
3. Break into subtasks across sessions

### Lost important context

1. Use `/rewind` to restore
2. Re-read critical files
3. Summarize key decisions in your message

### Slow responses

1. Context may be full - try `/compact`
2. Reduce file reading scope
3. Filter command outputs
