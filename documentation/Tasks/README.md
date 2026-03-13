# KALFA Task Management

Task tracking and sprint planning for KALFA development.

## Task Categories

### By Project
Create task files per project:
```
Tasks/
├── Project Name/
│   ├── Sprint 1.md
│   ├── Sprint 2.md
│   └── Backlog.md
└── Active Sprint.md
```

### By Type
- **Development** - Coding, features, bugs
- **Architecture** - Design, ADRs, research
- **Testing** - Unit, integration, E2E
- **DevOps** - Deployment, infrastructure, monitoring
- **Documentation** - Writing docs, creating diagrams

## Creating Tasks

### Daily Tasks
Document tasks in [[Daily/]] during daily log:
```markdown
### Today's Plan
- [ ] [Task] - [<% (i * 2) %>d] - [Priority]
- [ ] [Task] - [<% (i * 2) %>d] - [Priority]
```

### Sprint Planning
Create sprint note from template:
```markdown
# Sprint X - [Date Range]

## Goals
- [Goal 1]
- [Goal 2]
- [Goal 3]

## Tasks
- [ ] [Task] - [Owner] - [Points]
- [ ] [Task] - [Owner] - [Points]

## Blockers
- [Blocker] - [Impact]
```

## Task Properties

Use these checkboxes with metadata:
```markdown
- [ ] Task description
  - Owner: [Name]
  - Priority: [Critical/High/Medium/Low]
  - Due: [YYYY-MM-DD]
  - Related to: [[Project]] or [[Architecture/]]
  - Status: [Not Started/In Progress/Blocked/Testing]
  - Story Points: [Estimate]
```

## Tagging Tasks

### Status Tags
- `#active` - Currently working on
- `#blocked` - Waiting on dependency
- `#testing` - In QA phase
- `#completed` - Done
- `#archived` - No longer relevant

### Priority Tags
- `#priority/critical` - Must do now
- `#priority/high` - Important
- `#priority/medium` - Normal
- `#priority/low` - Nice to have

### Domain Tags
- `#frontend` - Livewire, Alpine, Tailwind
- `#backend` - Laravel, Services, Jobs
- `#database` - Migrations, Queries
- `#api` - Endpoints, Routes
- `#infrastructure` - DevOps, Deployment
- `#documentation` - Writing docs

## Querying Tasks

### Active Tasks by Priority
```dataview
TASK FROM #task
WHERE !completed AND !blocked
SORT priority DESC, due ASC
```

### Tasks by Project
```dataview
TABLE file.tags, description, priority, due
FROM #task
WHERE contains([[Project/]])
```

### My Tasks Today
```dataview
TASK FROM #task
WHERE due = date("<% tp.date.now("YYYY-MM-DD") %>")
```

---

## Related
- [[Projects/]] - Projects these tasks support
- [[Daily/]] - Daily task progress
- [[Meetings/]] - Task assignments from meetings
- [[Architecture/]] - Architecture tasks
