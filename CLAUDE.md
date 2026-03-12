# KALFA PKM Vault Context

## System Purpose
Complete developer PKM and documentation system for the KALFA Laravel SaaS platform - multi-tenant RSVP and seating management application.

### Primary Focus Areas
- **Technical Architecture** - Laravel 12 + Livewire 4 patterns and decisions
- **SaaS Development** - Multi-tenancy, billing, notifications
- **Knowledge Management** - Documenting patterns, best practices, and learnings
- **Project Tracking** - Active projects, sprints, and task management

## Directory Structure

| Folder | Purpose |
|--------|---------|
| `Architecture/` | Technical architecture, ADRs, API/Service/Database docs |
| `Projects/` | Active development projects with CLAUDE.md |
| `Tasks/` | Task tracking, sprint planning, blockers |
| `Daily/` | Daily engineering logs, standup notes |
| `Meetings/` | Meeting notes, design reviews, retros |
| `Templates/` | Templater templates for rapid note creation |
| `Dashboards/` | Obsidian Canvas views, project boards |
| `Knowledge/` | General knowledge base, best practices, articles |
| `Goals/` | Goal cascade (optional - if used) |
| `Archives/` | Completed projects, old sprints, retired docs |

## Current Focus

**Active Sprint:** Sprint <% Math.ceil(new Date().getDate() / 14) %>
**Primary Project:** [[Projects/]]
**Architecture Focus:** [[Architecture/]]

See [[Daily/]] for today's work log.

## Tag System

**Type:** `#daily`, `#meeting`, `#project`, `#adr`, `#architecture`, `#api`, `#service`, `#knowledge`, `#task`
**Status:** `#planning`, `#active`, `#on-hold`, `#review`, `#complete`, `#blocked`
**Priority:** `#priority/critical`, `#priority/high`, `#priority/medium`, `#priority/low`
**Domain:** `#auth`, `#multi-tenant`, `#events`, `#rsvp`, `#seating`, `#payments`, `#notifications`, `#infrastructure`

## Installed Plugins

- **Templater** - Template automation (`Templates/` folder)
- **Dataview** - Query vault data, create tables/lists
- **Tasks** - Task management in Obsidian
- **Pixel Banner** - Visual enhancements for notes
- **Supercharged Links** - Improved link styling and navigation
- **Style Settings** - Custom CSS and theming
- **Iconize** - File type icons in explorer
- **CardBoard** - Kanban-style boards for tasks/projects
- **Git** - Git integration for version control
- **Terminal** - Terminal window within Obsidian
- **Large Language Models** - AI assistance integration

## Quick Templates

Press `Ctrl/Cmd + P` to access templates:

| Template | Purpose | When to use |
|----------|---------|-------------|
| `Daily.md` | Daily engineering log | Every day - morning/evening |
| `Meeting.md` | Meeting notes | Standups, reviews, design sessions |
| `Project.md` | Project documentation | Starting a new project |
| `Architecture.md` | ADR (Architecture Decision Record) | Documenting significant technical decisions |

### Progress Visibility

Skills and agents use session task tools to show progress during multi-step operations:

```
[Spinner] Creating daily note...
[Spinner] Pulling incomplete tasks...
[Done] Morning routine complete (4/4 tasks)
```

Session tasks are temporary progress indicators—your actual to-do items remain as markdown checkboxes in daily notes.

## Available Agents

| Agent | Purpose |
|-------|---------|
| `note-organizer` | Organize vault, fix links, consolidate notes |
| `weekly-reviewer` | Facilitate weekly review aligned with goals |
| `goal-aligner` | Check daily/weekly alignment with long-term goals |
| `inbox-processor` | GTD-style inbox processing |

## Output Styles

**Technical & Concise** - Clear, precise responses. Focus on:
- Code quality and patterns
- Architecture trade-offs
- Practical solutions
- Direct answers without fluff

---

## Key Workflows

The full goals-to-tasks flow:

```
3-Year Vision  →  Yearly Goals  →  Projects  →  Monthly Goals  →  Weekly Review  →  Daily Tasks
   /goal-tracking     /project        /project      /monthly          /weekly         /daily
```

## Daily Workflow

### Morning (5 min)
1. Run `/daily` to create today's note
2. Review cascade context (ONE Big Thing, project next-actions)
3. Identify ONE main focus
4. Review yesterday's incomplete tasks
5. Set time blocks

### Evening (5 min)
1. Complete reflection section
2. Review goal/project attention summary
3. Move unfinished tasks
4. Run `/push` to save changes

### Weekly (30 min - Sunday)
1. Run `/weekly` for guided review
2. Review project progress table
3. Calculate goal progress
4. Plan next week's focus
5. Archive old notes

### Monthly (30 min - End of month)
1. Run `/monthly` for guided review
2. Roll up weekly wins/challenges
3. Check quarterly milestones
4. Plan next month's focus

## Best Practices

1. **Be Specific** - Give clear context about what you need
2. **Reference Goals** - Connect daily tasks to objectives
3. **Use Coach Mode** - When you need accountability
4. **Keep It Current** - Update project CLAUDE.md files regularly

## Customization

For personal overrides that shouldn't be committed, create `CLAUDE.local.md`.
See `CLAUDE.local.md.template` for format.

---

*See @.claude/rules/ for detailed conventions*
*Last Updated: 2026-02-15*
*System Version: 3.1*


<claude-mem-context>
# Recent Activity

<!-- This section is auto-generated by claude-mem. Edit content outside the tags. -->

*No recent activity*
</claude-mem-context>