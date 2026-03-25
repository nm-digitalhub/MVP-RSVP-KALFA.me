# MCP Server Configuration

MCP (Model Context Protocol) extends Claude Code with external tools, resources, and prompts from servers.

## What MCP Provides

| Feature | Description | Example |
|---------|-------------|---------|
| **Tools** | New capabilities | Create GitHub issues, query databases |
| **Resources** | External data | Database schemas, API docs |
| **Prompts** | Predefined operations | Canned queries, workflows |

## Transport Types

MCP servers communicate via different transports:

### stdio (Standard I/O)

Most common. Server runs as subprocess:

```bash
claude mcp add my-server --command "node" --args "server.js"
```

### HTTP

Server at HTTP endpoint:

```bash
claude mcp add api-server --transport http --url "http://localhost:3001/mcp"
```

### SSE (Server-Sent Events)

Streaming endpoint:

```bash
claude mcp add stream-server --transport sse --url "http://localhost:3001/sse"
```

## Installation Commands

### Basic Installation

```bash
# stdio server with command
claude mcp add <name> --command "<cmd>" --args "<args>"

# HTTP server
claude mcp add <name> --transport http --url "<url>"

# SSE server
claude mcp add <name> --transport sse --url "<url>"
```

### With Environment Variables

```bash
claude mcp add github --command "npx" --args "-y @modelcontextprotocol/server-github" \
  --env GITHUB_TOKEN=ghp_xxx
```

### Scopes

```bash
# Project-local (default) - saved to .mcp.json
claude mcp add local-server --command "node" --args "server.js"

# User-level - saved to ~/.claude.json
claude mcp add -s user global-server --command "node" --args "server.js"
```

## Configuration Files

### Project: `.mcp.json`

```json
{
  "mcpServers": {
    "database": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-postgres"],
      "env": {
        "DATABASE_URL": "${DATABASE_URL}"
      }
    },
    "github": {
      "command": "npx",
      "args": ["-y", "@modelcontextprotocol/server-github"],
      "env": {
        "GITHUB_TOKEN": "${GITHUB_TOKEN}"
      }
    }
  }
}
```

### User: `~/.claude.json`

```json
{
  "mcpServers": {
    "obsidian": {
      "command": "npx",
      "args": ["-y", "obsidian-mcp-server"],
      "env": {
        "VAULT_PATH": "/path/to/vault"
      }
    }
  }
}
```

## Environment Variable Expansion

Use `${VAR}` syntax for environment variables:

```json
{
  "mcpServers": {
    "database": {
      "command": "node",
      "args": ["db-server.js"],
      "env": {
        "DB_HOST": "${DB_HOST}",
        "DB_PASSWORD": "${DB_PASSWORD}"
      }
    }
  }
}
```

Variables are read from your shell environment.

## Managing Servers

### List Servers

```bash
claude mcp list
```

### Remove Server

```bash
claude mcp remove <name>
```

### Server Status

```bash
# In Claude Code
/status
```

Shows connected MCP servers and available tools.

## Using MCP Tools

Once configured, MCP tools appear automatically:

```
# Claude can use tools like:
github:create_issue
database:query
slack:send_message
```

### Tool Search

Enable tool search to discover MCP tools:

In settings:
```json
{
  "ENABLE_TOOL_SEARCH": true
}
```

Claude will search MCP tools when they might be useful.

## Using MCP Resources

Access resources with `@server:resource` syntax:

```
@database:schema    # Database schema
@github:repos       # Repository list
@docs:api-reference # Documentation
```

### In Prompts

```
Using @database:schema, write a query to find all users created this month.
```

## OAuth Authentication

Some MCP servers require OAuth:

```bash
# Add server that needs auth
claude mcp add linear --command "npx" --args "-y @linear/mcp-server"

# Claude Code will prompt for OAuth when first used
```

The OAuth flow:
1. Server requests authentication
2. Browser opens for login
3. Token stored securely
4. Subsequent uses auto-authenticate

## Popular MCP Servers

### GitHub

```bash
claude mcp add github \
  --command "npx" \
  --args "-y @modelcontextprotocol/server-github" \
  --env GITHUB_TOKEN=ghp_xxx
```

Tools: create_issue, create_pr, get_file, search_code

### PostgreSQL

```bash
claude mcp add postgres \
  --command "npx" \
  --args "-y @modelcontextprotocol/server-postgres" \
  --env DATABASE_URL=postgres://...
```

Tools: query, list_tables, describe_table

### Sentry

```bash
claude mcp add sentry \
  --command "npx" \
  --args "-y @sentry/mcp-server" \
  --env SENTRY_AUTH_TOKEN=xxx
```

Tools: get_issues, get_issue_details, resolve_issue

### Filesystem

```bash
claude mcp add fs \
  --command "npx" \
  --args "-y @modelcontextprotocol/server-filesystem" \
  --args "/allowed/path"
```

Tools: read_file, write_file, list_directory

### Memory/Notes

```bash
claude mcp add memory \
  --command "npx" \
  --args "-y @modelcontextprotocol/server-memory"
```

Tools: save_note, get_notes, search_notes

## Building Custom MCP Servers

### TypeScript Example

```typescript
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";

const server = new Server({
  name: "my-server",
  version: "1.0.0"
}, {
  capabilities: {
    tools: {}
  }
});

server.setRequestHandler("tools/list", async () => ({
  tools: [{
    name: "my_tool",
    description: "Does something useful",
    inputSchema: {
      type: "object",
      properties: {
        input: { type: "string" }
      },
      required: ["input"]
    }
  }]
}));

server.setRequestHandler("tools/call", async (request) => {
  if (request.params.name === "my_tool") {
    const result = doSomething(request.params.arguments.input);
    return { content: [{ type: "text", text: result }] };
  }
});

const transport = new StdioServerTransport();
await server.connect(transport);
```

### Python Example

```python
from mcp.server import Server
from mcp.server.stdio import stdio_server

server = Server("my-server")

@server.tool()
async def my_tool(input: str) -> str:
    """Does something useful"""
    return do_something(input)

async def main():
    async with stdio_server() as (read, write):
        await server.run(read, write)
```

## Troubleshooting

### Server Won't Connect

1. Check command is correct: `which npx`, `which node`
2. Verify args syntax: proper quoting and spacing
3. Check env vars are set: `echo $VAR_NAME`
4. View logs: `/status` in Claude Code

### Tools Not Appearing

1. Restart Claude Code after adding server
2. Check `/status` for server connection
3. Verify server implements tools/list handler
4. Check for error messages in server output

### Authentication Failures

1. Verify tokens/credentials are current
2. Check token permissions/scopes
3. Try removing and re-adding server
4. Check for OAuth expiration

## Best Practices

### 1. Use Project Scope for Project Tools

```bash
# Project-specific database
claude mcp add project-db --command "..."
# Saved to .mcp.json, shared with team
```

### 2. Use User Scope for Personal Tools

```bash
# Personal note-taking
claude mcp add -s user notes --command "..."
# Saved to ~/.claude.json, only you
```

### 3. Secure Credentials

```json
{
  "mcpServers": {
    "api": {
      "env": {
        "API_KEY": "${API_KEY}"
      }
    }
  }
}
```

Store secrets in environment, not config files.

### 4. Document MCP Requirements

In CLAUDE.md:
```markdown
## Required MCP Servers
This project requires:
- `database`: PostgreSQL access (see .mcp.json)
- Set DATABASE_URL in your environment
```
