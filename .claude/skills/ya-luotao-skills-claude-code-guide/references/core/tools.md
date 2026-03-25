# Built-in Tools Reference

Claude Code has access to a set of built-in tools for interacting with your system. Understanding these tools helps you work with Claude Code effectively.

## File Operations

### Read

Reads file contents from disk.

```
Capabilities:
- Read any file type (code, config, data)
- Read images (PNG, JPG) - displayed visually
- Read PDFs - text and visual extraction
- Read Jupyter notebooks - cells with outputs
- Partial reads with offset/limit for large files
```

**Usage patterns:**
```
# Full file read
"Read src/auth/login.ts"

# Specific section
"Read lines 50-100 of the config file"

# Multiple files
"Read the package.json and tsconfig.json"
```

### Edit

Makes targeted changes to existing files.

```
Capabilities:
- Find and replace specific text
- Preserves file formatting
- Works with any text file
- Atomic operations (all-or-nothing)
```

**Best for:**
- Small, targeted changes
- Preserving surrounding code
- Refactoring specific sections

### Write

Creates new files or completely replaces existing ones.

```
Capabilities:
- Create new files
- Overwrite entire file contents
- Create directories as needed
```

**Best for:**
- New files
- Complete file rewrites
- Generated content

### Glob

Finds files matching patterns.

```
Pattern examples:
*.ts                 # All TypeScript files in current dir
**/*.ts              # All TypeScript files recursively
src/**/*.test.ts     # Test files in src
{src,lib}/**/*.js    # JS files in src or lib
!**/node_modules/**  # Exclude node_modules
```

**Use cases:**
- Finding files by extension
- Discovering project structure
- Locating test files

### LS (via Bash)

Lists directory contents.

```bash
ls -la src/           # Detailed listing
ls -R src/components  # Recursive listing
```

## Search Tools

### Grep

Searches file contents using regular expressions.

```
Capabilities:
- Regex pattern matching
- File type filtering
- Context lines (-B, -A, -C)
- Count matches
- Case insensitive (-i)
```

**Examples:**
```
# Find function definitions
"Search for 'function handleAuth' in src/"

# Find all TODO comments
"Grep for 'TODO:' in TypeScript files"

# Find imports
"Search for 'from.*react' in components/"
```

**Output modes:**
- `files_with_matches` - Just file paths (default)
- `content` - Show matching lines
- `count` - Number of matches per file

## Execution

### Bash

Runs shell commands.

```
Capabilities:
- Any shell command
- Environment variable access
- Piping and redirection
- Background execution
- Timeout control (up to 10 min)
```

**Common uses:**
```bash
# Git operations
git status
git diff HEAD~1
git log --oneline -10

# Package management
npm install
pip install -r requirements.txt

# Build and test
npm run build
pytest tests/

# Process management
ps aux | grep node
lsof -i :3000
```

**Background execution:**
```
Run this in the background: npm run dev
```

Returns a task ID for later status checks.

### Task (Subagents)

Spawns specialized agents for complex operations.

```
Built-in agent types:
- Explore: Codebase exploration
- Plan: Architecture planning
- Bash: Command execution
- general-purpose: Multi-step tasks
```

**Use cases:**
- Parallel investigations
- Complex multi-step operations
- Isolated context for focused work

See `../configuration/sub-agents.md` for details.

## Web Tools

### WebFetch

Fetches and processes web content.

```
Capabilities:
- Fetches URL content
- Converts HTML to markdown
- Processes with AI for extraction
- 15-minute cache
```

**Limitations:**
- No authenticated content (use MCP for that)
- HTTP auto-upgraded to HTTPS
- May follow redirects

**Usage:**
```
"Fetch the React documentation page and summarize the hooks API"
```

### WebSearch

Searches the web for information.

```
Capabilities:
- Web search queries
- Domain filtering (allow/block)
- Returns formatted results
```

**Usage:**
```
"Search for 'TypeScript 5.0 new features'"
```

**Note:** Results include source URLs for citation.

## Communication

### AskUserQuestion

Asks clarifying questions during execution.

```
Capabilities:
- Multiple choice options
- Multi-select support
- Collects structured input
```

**When used:**
- Ambiguous requirements
- Multiple valid approaches
- User preferences needed

## Notebook Tools

### NotebookEdit

Edits Jupyter notebook cells.

```
Capabilities:
- Replace cell contents
- Insert new cells
- Delete cells
- Change cell type (code/markdown)
```

## Tool Permissions

Tools require different permission levels:

| Tool | Default Permission |
|------|-------------------|
| Read, Glob, Grep | Auto-approved |
| WebFetch, WebSearch | Auto-approved |
| Edit, Write | Requires approval |
| Bash (safe commands) | Auto-approved |
| Bash (risky commands) | Requires approval |

**Safe command patterns (auto-approved):**
- `git status`, `git diff`, `git log`
- `npm test`, `pytest`, `cargo test`
- `ls`, `cat`, `head`, `tail`
- `node --version`, `python --version`

**Risky patterns (require approval):**
- `rm`, `mv` (destructive)
- `git push`, `git reset`
- Unknown commands
- Commands with `sudo`

## Tool Selection Guide

| Task | Recommended Tool |
|------|------------------|
| Read a specific file | Read |
| Find files by name/pattern | Glob |
| Search code contents | Grep |
| Make small code changes | Edit |
| Create new files | Write |
| Run tests | Bash |
| Git operations | Bash |
| Install packages | Bash |
| Explore codebase | Task (Explore agent) |
| Check documentation | WebFetch |
| Research solutions | WebSearch |

## Tool Chaining Examples

### Bug Investigation
```
1. Grep("error message") → Find where error occurs
2. Read(file.ts) → Understand context
3. Grep("function name") → Find related code
4. Edit(file.ts) → Fix the bug
5. Bash("npm test") → Verify fix
```

### New Feature
```
1. Glob("**/*.ts") → Understand structure
2. Read(similar-feature.ts) → Learn patterns
3. Write(new-feature.ts) → Create file
4. Edit(index.ts) → Add export
5. Write(new-feature.test.ts) → Add tests
6. Bash("npm test") → Verify
```

### Code Review
```
1. Bash("git diff main") → See changes
2. Read(changed files) → Understand changes
3. Grep(patterns) → Check for issues
4. Report findings
```

## MCP Tools

MCP servers can add custom tools:

```
Examples:
- github:create_issue
- slack:send_message
- database:query
- sentry:get_issues
```

See `../configuration/mcp.md` for adding MCP tools.
