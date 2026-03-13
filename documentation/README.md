# KALFA Documentation Vault

A complete PKM (Personal Knowledge Management) system for the KALFA Laravel SaaS platform.

---

## 🚀 Quick Start

### Daily Workflow
1. Create daily note: `Ctrl+P → Templates/Daily.md`
2. Track development work, architecture decisions, and learnings
3. Update project status and task progress
4. Sync: `ob sync`

### Meeting Workflow
1. Before meeting: `Ctrl+P → Templates/Meeting.md`
2. Capture decisions, action items, and technical discussions
3. Link action items to [[Tasks/]] and [[Projects/]]

### Architecture Documentation
1. For each major decision: `Ctrl+P → Templates/Architecture.md`
2. Document context, alternatives, and consequences
3. Link to affected [[Projects/]] and [[Architecture/]]

---

## 📂 Vault Structure

### `/Architecture`
Technical architecture documentation and ADRs (Architecture Decision Records).
- **APIs** - RESTful API documentation
- **Services** - Service layer and business logic
- **Database** - Schema, migrations, data flow
- **Infrastructure** - Deployment, CI/CD, monitoring

### `/Projects`
Project documentation for KALFA development efforts.
Each project has its own folder with:
- `CLAUDE.md` - Project context and status
- Project notes and documentation
- Linked to [[Architecture/]] ADRs

### `/Tasks`
Task tracking and sprint management.
- Task breakdown by project
- Sprint planning
- Blocker tracking

### `/Daily`
Daily development logs and standup notes.
- What I shipped today
- Architecture decisions made
- Learnings and questions
- Meeting notes

### `/Meetings`
Meeting notes and action items.
- Standups, planning sessions, retros
- Design reviews, code reviews
- Stakeholder meetings

### `/Templates`
Templater templates for rapid note creation.
- `Daily.md` - Daily engineering log
- `Meeting.md` - Meeting notes template
- `Project.md` - Project documentation
- `Architecture.md` - ADR template

### `/Dashboards`
Obsidian Canvas and dashboard views.
- Project overview canvases
- Architecture diagrams
- Sprint boards

### `/Knowledge`
General knowledge base and articles.
- Technology deep-dives
- Best practices
- Troubleshooting guides
- Onboarding materials

---

## 🎯 Key Workflows

### Documenting a New Feature
1. **Planning:** Create [[Projects/]] entry from template
2. **Architecture:** Create [[Architecture/]] ADR for significant decisions
3. **Daily:** Track progress in [[Daily/]] notes
4. **Meeting:** Document design reviews and planning sessions
5. **Knowledge:** Create [[Knowledge/]] article for reusable learnings

### Handling Architecture Decisions
1. **Proposal:** Create ADR from `Templates/Architecture.md`
2. **Discussion:** Link to [[Meetings/]] review notes
3. **Decision:** Update ADR status to "Accepted"
4. **Implementation:** Reference ADR in daily logs
5. **Post-implementation:** Update ADR with results and metrics

### Sprint Workflow
1. **Plan:** Create sprint in [[Tasks/]] with project breakdown
2. **Execute:** Daily logs track what was shipped
3. **Blockers:** Document blockers in [[Daily/]] and [[Tasks/]]
4. **Retro:** Use `Templates/Meeting.md` for retrospective
5. **Archive:** Move completed sprint to [[Archives/]]

---

## 🏷️ Tags

### Type Tags
- `#daily` - Daily notes
- `#meeting` - Meeting notes
- `#project` - Project documentation
- `#adr` - Architecture Decision Records
- `#architecture` - Technical design docs
- `#api` - API documentation
- `#service` - Service documentation
- `#knowledge` - Knowledge base articles
- `#task` - Task entries

### Status Tags
- `#planning` - In planning phase
- `#active` - Currently being worked on
- `#on-hold` - Temporarily paused
- `#review` - Ready for review
- `#complete` - Finished
- `#blocked` - Waiting on dependency

### Priority Tags
- `#priority/critical` - Must do now
- `#priority/high` - Important
- `#priority/medium` - Normal priority
- `#priority/low` - Nice to have

### Domain Tags
- `#auth` - Authentication & authorization
- `#multi-tenant` - Organization context
- `#events` - Event management
- `#rsvp` - RSVP system
- `#seating` - Tables & seat assignments
- `#payments` - Billing & payments
- `#notifications` - Twilio integration
- `#infrastructure` - DevOps & deployment

---

## 🔧 Configuration

### Installed Plugins
- **Templater** - Template automation
- **Dataview** - Query vault data
- **Tasks** - Task management
- **Pixel Banner** - Visual enhancements
- **Supercharged Links** - Link improvements
- **Style Settings** - Custom styling
- **Iconize** - File icons
- **CardBoard** - Kanban boards
- **Git** - Git integration
- **Terminal** - Terminal in Obsidian
- **Large Language Models** - AI assistance

### Obsidian Sync
Vault is synced with Obsidian Cloud via `ob` CLI.
- Run `ob sync` to push/pull changes
- Vault name: KALFA-Docs
- Device: NM-DigitalHUB

---

## 📊 Quick Queries

### Active Projects
```dataview
TABLE without id
FROM #project AND #active
SORT created DESC
```

### Recent ADRs
```dataview
TABLE WITHOUT ID file.tags, status, date
FROM #adr
SORT date DESC
LIMIT 10
```

### Tasks by Priority
```dataview
TASK
FROM #task
WHERE !completed
SORT priority DESC, due ASC
```

### Daily Log Summary
```dataview
LIST
FROM #daily
SORT date DESC
LIMIT 7
```

---

## 🔗 Quick Navigation

### Start Here
- [[Architecture/README]] - Architecture overview
- [[Projects/]] - Active projects
- [[Tasks/]] - Current tasks
- [[Daily/<% tp.date.now("YYYY-MM-DD") %>]] - Today's log

### Templates
- [[Templates/Daily]] - Daily log template
- [[Templates/Meeting]] - Meeting notes template
- [[Templates/Project]] - Project template
- [[Templates/Architecture]] - ADR template

### Goals & Focus
- [[Goals/]] - Goal cascade (if using)
- [[Dashboards/]] - Visual dashboards

---

*Last Updated: <% tp.date.now("YYYY-MM-DD") %>*
*Vault Version: 1.0*
