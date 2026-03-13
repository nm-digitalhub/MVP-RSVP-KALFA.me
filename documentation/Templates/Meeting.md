---
date: <% tp.date.now("YYYY-MM-DD HH:mm") %>
type: meeting
tags: [meeting, kalfa, development]
---

# <% tp.system.prompt("Enter meeting title") %>

## 📋 Meeting Details
<%*
  const meetingType = tp.system.suggester("Meeting type", ["Standup", "Planning", "Design Review", "Code Review", "Retro", "Stakeholder", "Client"]);
  const attendees = tp.system.prompt("Who attended? (comma separated)");
  const duration = tp.system.prompt("Duration in minutes?", "60");
*-%>
**Date:** <% tp.date.now("dddd, MMMM DD, YYYY") %>
**Time:** <% tp.date.now("HH:mm") %>
**Duration:** <% duration %> minutes
**Type:** <% meetingType %>
**Location:** [Room/Link/Platform]

### Attendees
<% attendees.split(',').forEach((person, i) { %>
- <% person.trim() %>
<% }); %>

---

## 🎯 Meeting Purpose

### Objectives
*Why are we meeting? What do we need to accomplish?*

1. [Objective 1]
2. [Objective 2]
3. [Objective 3]

### Expected Outcomes
*What should we walk away with?*

- [Outcome 1]
- [Outcome 2]

---

## 📝 Meeting Notes

### Discussion Points
<%*
  const sections = parseInt(tp.system.prompt("How many discussion sections?", "3"));
  for (let i = 0; i < sections; i++) {
*-%>

#### Section <% i + 1 %>: [Title]
**Time:** [Start - End]
**Key Points:**
- [Point 1]
- [Point 2]

**Decisions Made:**
- **Decision:** [What was decided?]
- **Rationale:** [Why this decision?]
- **Impact:** [What does this affect?]

**Questions Raised:**
- [Question 1] - [Who will answer?]
- [Question 2] - [Who will answer?]

<% } %>

---

## ✅ Action Items

<%*
  const actionCount = parseInt(tp.system.prompt("How many action items?", "3"));
  for (let i = 0; i < actionCount; i++) {
*-%>
<% let assignee = tp.system.suggester("Action <% i + 1 %> Owner", ["Me", "Team", "External", "Deferred"]); %>
### Action <% i + 1 %>
- **Task:** [What needs to be done?]
- **Owner:** <% assignee %>
- **Due Date:** [YYYY-MM-DD]
- **Priority:** [High/Medium/Low]
- **Related to:** [[Project]] or [[Architecture/API/]]
- **Status:** [Not Started/In Progress/Blocked]

<% } %>

---

## 📊 Outcomes

### Decisions Summary
<%*
  const decisions = parseInt(tp.system.prompt("How many key decisions?", "3"));
  for (let i = 0; i < decisions; i++) {
*-%>
**Decision <% i + 1 %>:** [Title]
- **Context:** [Background info]
- **Choice:** [What was chosen]
- **Alternatives considered:** [Options rejected]
- **Next steps:** [Implementation actions]

<% } %>

### Risks Identified
- **Risk:** [Description]
  - **Likelihood:** [High/Medium/Low]
  - **Impact:** [High/Medium/Low]
  - **Mitigation:** [How to handle]

---

## 🎨 Technical Discussions

### Architecture Review
- **Component/System:** [What's being reviewed?]
- **Current State:** [How it works now]
- **Proposed Change:** [What's changing?]
- **Trade-offs:** [Pros/Cons]
- **Decision:** [Go/No-go/Defer]

### Code Review
- **PR/Commit:** [Reference]
- **Files changed:** [Key files]
- **Approvals needed:** [Who needs to review?]
- **Concerns raised:**
  - [Concern 1] - [Severity]
  - [Concern 2] - [Severity]

---

## 📎 Follow-up

### Next Meeting
- **Date:** [YYYY-MM-DD]
- **Time:** [HH:mm]
- **Purpose:** [Why meet again?]

### Documentation Needed
- [ ] Update [[Architecture/API/]] documentation
- [ ] Create RFC for [feature/change]
- [ ] Update [[Projects/]] status
- [ ] Send summary to team

---

## 🔗 Related
- Linked to: [[Project]]
- Architecture impacted: [[Architecture/]]
- Follows: [[Meeting/<% tp.date.now("-7d", "YYYY-MM-DD") %>]] (if recurring)
- [[Daily/<% tp.date.now("YYYY-MM-DD") %>]] - Today's log

---

*Meeting ID: <% tp.date.now("YYYYMMDD-HHmm") %>*
*Duration: <% duration %> minutes*
*Next sync: <% tp.date.now("+1d", "dddd") %>*

**Action Item Status:** [<% actionCount %> items assigned]
