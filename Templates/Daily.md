---
date: <% tp.date.now("YYYY-MM-DD") %>
type: daily
tags: [daily, development, kalfa]
---

# Daily Log - <% tp.date.now("dddd, MMMM DD, YYYY") %>

<%*
  const sprintNum = Math.ceil(tp.date.now("D") / 14);
  const quarter = Math.floor(tp.date.now("M") / 3) + 1;
*-%>

## 📋 Context
**Sprint:** Sprint <% sprintNum %>
**Quarter:** Q<% quarter %>
**Work Week:** <% tp.date.now("w") %> / 52

---

## 🎯 Primary Focus
*What's ONE thing I must ship today?*

**Today's Priority:**

---

## ⚡ Morning Standup

### Yesterday's Progress
- **Completed:**
- **Blocked on:**
- **Learned:**

### Today's Plan
- **Feature/Task 1:**
- **Feature/Task 2:**
- **Feature/Task 3:**

---

## 💻 Development Work

### Code Changes
- **File/Module:** [Path or module name]
  - Type: [Feature/Bug/Refactor]
  - Description:
  - Lines: ~[X]

### Architecture Decisions
- **Context:** [What led to this decision?]
- **Decision:** [What was decided?]
- **Rationale:** [Why this approach?]
  - Alternative considered:
  - Trade-offs:
- **Impact:** [What components affected?]

### Code Review Feedback
- **PR/Commit:** [Reference]
  - **Reviewer:** [Name]
  - **Feedback:**
  - **Action:** [Accept/Changes needed]

---

## 🗄️ API & Services

### API Changes
- **Endpoint:** [Method /path]
  - Change type: [New/Updated/Deprecated]
  - Impact: [Breaking/Non-breaking]
  - Migration needed: [Y/N]

### Database Changes
- **Migration:** [Name or timestamp]
  - Type: [Add/Modify/Drop]
  - Tables affected:
  - Backwards compatible: [Y/N]

### Service Updates
- **Service:** [Name]
  - Change: [What changed?]
  - Tests added: [Y/N]

---

## 🧠 Knowledge Capture

### Learnings Today
- **New concept:** [What I learned]
  - Application: [How I'll use it]
- **Pattern discovered:** [Architecture/coding pattern]
- **Anti-pattern avoided:** [What not to do]

### Questions & Research
- **Question:** [Something I'm exploring]
  - Resources checked: [Links/Docs]
  - Status: [Researching/Resolved]

### Ideas to Explore
- [Feature idea for KALFA]
- [Architecture improvement]
- [Tool/framework to evaluate]

---

## 🐛 Issues & Bugs

### Bugs Filed
- **Issue:** [Description]
  - Severity: [Critical/High/Medium/Low]
  - Affected component:
  - Reproduction steps:
  - Workaround:

### Issues Resolved
- **Issue:** [Which bug was fixed]
  - Root cause: [Why it happened]
  - Solution: [How fixed]
  - Prevent future: [How to avoid]

---

## 📊 Metrics
- **PRs opened:** [X]
- **PRs merged:** [X]
- **Commits:** [X]
- **Lines of code:** [+X / -Y]
- **Test coverage:** [X%]
- **Build time:** [X min]
- **Code review time:** [X min]

---

## 📝 Meeting Notes

### [Meeting Title] - <% tp.date.now("HH:mm") %>
**Attendees:** [Names]
**Type:** [Standup/Planning/Retro/Design]
**Key Points:**
- [Point 1]
- [Point 2]
**Action Items:**
- [ ] [Task] - [Owner] - Due: [DATE]

---

## 🌅 Evening Wrap-up

### Accomplishments
1.
2.
3.

### Blockers
- **Blocking issue:** [What's stopping progress?]
- **Next action:** [What to unblock?]
- **Who can help:** [Person or team]

### Tomorrow's Plan
**Priority 1:**
**Priority 2:**
**Priority 3:**

### Energy & Reflection
- **Focus level:** [1-10]
- **Code quality:** [Proud/Needs improvement]
- **One thing learned today:**
- **Tomorrow's goal:**

---

## 🔗 Navigation
<%*
  const yesterday = tp.date.now("-1d", "YYYY-MM-DD");
  const tomorrow = tp.date.now("+1d", "YYYY-MM-DD");
*-%>
- Yesterday: [[Daily/<% yesterday %>]]
- Tomorrow: [[Daily/<% tomorrow %>]]
- Current Sprint: [[Projects/Sprint <% sprintNum %>]]
- Active Projects: [[Projects/]]

---

*Day <% tp.date.now("D") %> of the year*
*Focus: Ship quality, not just quantity.*
