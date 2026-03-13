# Weekly Review - Week of <% tp.date.now("YYYY-MM-DD") %>

## 🎯 Week at a Glance
**Week Number:** Week <% tp.date.now("w") %> of 52
**Theme/Focus:** [Define this week's primary focus]
**Energy Available:** [High/Medium/Low]

---

## 📊 Quick Stats
- **Tasks Completed:** [X/Y]
- **Projects Advanced:** [List projects worked on]
- **Habits Maintained:** [X/7 days]
- **One Metric Progress:** [Update from Goals/1. Yearly Goals.md]

---

## 🔍 Last Week Review

### What Went Well? (Wins) 🎉
1.
2.
3.

### What Didn't Go Well? (Challenges) 🔥
1.
2.
3.

### Key Lessons Learned 📚
-

### Incomplete Tasks (Carry Forward?)
- [ ] [Task] - Action: [Reschedule/Delegate/Delete]
- [ ] [Task] - Action: [Reschedule/Delegate/Delete]

---

## 📅 This Week's Plan

### 🎯 ONE Big Thing
**If I accomplish nothing else this week, I will:**

### Priority Matrix

#### 🔴 Urgent & Important
- [ ]
- [ ]

#### 🟡 Important Not Urgent
- [ ]
- [ ]

#### 🟢 Quick Wins
- [ ]
- [ ]

---

## 🗓️ Day by Day
<%*
  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
  const today = new Date();
  const currentDay = today.getDay(); // 0 = Sunday, 1 = Monday, etc.
  const monday = new Date(today);
  monday.setDate(today.getDate() - (currentDay === 0 ? 6 : currentDay - 1));

  for (let i = 0; i < 5; i++) {
    let dayDate = new Date(monday);
    dayDate.setDate(monday.getDate() + i);
    let dayNum = dayDate.getDate();
    let month = dayDate.getMonth() + 1;
*-%>

### <% days[i] %> <% tp.date.now("+" + i + "d", "MM/DD") %>
**Focus:**
- [ ] Priority task
- [ ]
<% } %>

### Weekend
**Personal/Family Focus:**
- [ ]
- [ ]

---

## 🏗️ Project Status

### Active Projects
1. **[Project Name]**
   - This week's goal:
   - Status: [On track/Behind/Ahead]
   - Next action:

2. **[Project Name]**
   - This week's goal:
   - Status: [On track/Behind/Ahead]
   - Next action:

---

## 🧘 Habits & Routines

### Habit Scorecard
- [ ] Daily Morning Routine (Target: 7/7)
- [ ] Exercise (Target: 3x)
- [ ] Meditation (Target: 5x)
- [ ] Reading (Target: 30 min/day)
- [ ] Weekly Review (Target: Sunday)

---

## 📚 Learning Focus

**This Week's Topic:**
**Resource:**
**Time Allocated:**
**Key Question to Answer:**

---

## 💭 Reflection

### Energy Check
- **Physical:** [1-10] - Plan:
- **Mental:** [1-10] - Plan:
- **Emotional:** [1-10] - Plan:

### This Week's Intention
*How do I want to show up?*

### Potential Obstacles & Strategies
1. **Obstacle:**
   - **Strategy:**

2. **Obstacle:**
   - **Strategy:**

---

## 📝 Brain Dump
*Ideas, thoughts, things to remember*

-
-
-

---

## ✅ Review Checklist
- [ ] Reviewed last week's accomplishments
- [ ] Processed all inbox items
- [ ] Updated project statuses
- [ ] Checked upcoming calendar
- [ ] Reviewed Goals/2. Monthly Goals.md
- [ ] Planned this week's priorities
- [ ] Blocked time for deep work
- [ ] Set ONE big thing for the week
- [ ] Cleaned up digital workspace
- [ ] Committed changes to Git

---

## 🔗 Navigation
<%*
  let monthlyFile = tp.file.findTFile("Goals/2. Monthly Goals.md");
  let yearlyFile = tp.file.findTFile("Goals/1. Yearly Goals.md");
  let lastWeek = tp.date.now("-7d", "YYYY-MM-DD");
  let nextWeek = tp.date.now("+7d", "YYYY-MM-DD");
*-%>
<% if (monthlyFile) { %>[[<% monthlyFile.basename %>]]<% } %> - Current Month
<% if (yearlyFile) { %>[[<% yearlyFile.basename %>]]<% } %> - Current Year
- Previous: [[<% lastWeek %> Weekly Review]]
- Next: [[<% nextWeek %> Weekly Review]]

---

*Review Started: <% tp.date.now("HH:mm") %>*
*Review Completed: [TIME]*
*Time Invested: [X minutes]*

**This Week's Mantra:**
