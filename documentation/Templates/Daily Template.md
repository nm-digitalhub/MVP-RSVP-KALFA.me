---
date: <% tp.date.now("YYYY-MM-DD") %>
tags: daily-note
---

# <% tp.date.now("dddd, MMMM DD, YYYY") %>

*Build meaningful technology while maintaining balance across health, relationships, and personal growth.*

---

## 🎯 Today's Focus
*What's the ONE thing that would make today a win?*

**Today's Priority:**

---

## ⏰ Time Blocks
*Plan your day with intentional time allocation*

- **Morning (6-9am):** [Morning routine/Deep work]
- **Mid-Morning (9-12pm):** [Primary work block]
- **Afternoon (12-3pm):** [Meetings/Collaboration]
- **Late Afternoon (3-6pm):** [Secondary work block]
- **Evening (6-9pm):** [Personal/Family time]

---

## ✅ Tasks

### 🔴 Must Do Today
- [ ] [PRIORITY: Task that must be completed]
- [ ] [PRIORITY: Critical deadline or commitment]

### 💼 Work
- [ ] [Work task 1]
- [ ] [Work task 2]
- [ ] [Work task 3]

### 🏠 Personal
- [ ] [Personal task 1]
- [ ] [Personal task 2]

### 📚 Learning/Growth
- [ ] [Learning activity]
- [ ] [Reading/Course progress]

### 🏃 Health/Wellness
- [ ] Morning routine completed
- [ ] Exercise: [Type and duration]
- [ ] Meditation: [X minutes]
- [ ] Water intake: [X glasses]

---

## 💡 Ideas & Thoughts
*Capture anything that comes to mind*

-

---

## 📝 Notes from Today
*Meeting notes, important information, key decisions*

### Meetings
- **[Meeting Name]:**
  - Key points:
  - Action items:

### Important Info
-

---

## 🌟 Gratitude
*Three things I'm grateful for today*

1.
2.
3.

---

## 🔍 End of Day Reflection
*Complete before bed*

### What Went Well?
-

### What Could Be Better?
-

### What Did I Learn?
-

### Tomorrow's #1 Priority
-

### Energy Level Today
- Physical: [1-10]
- Mental: [1-10]
- Emotional: [1-10]

---

## 📊 Daily Metrics
- Deep Work Time: [X hours]
- Shallow Work Time: [X hours]
- Tasks Completed: [X/Y]
- Inbox Zero: [Y/N]
- Screen Time: [X hours]

---

## 🔗 Related
<%*
  let weeklyFile = tp.file.findTFile("Goals/3. Weekly Review.md");
  let monthlyFile = tp.file.findTFile("Goals/2. Monthly Goals.md");
  let yesterday = tp.date.now("-1d", "YYYY-MM-DD");
  let tomorrow = tp.date.now("+1d", "YYYY-MM-DD");
  let weekNum = tp.date.now("w");
  let dayOfYear = tp.date.now("D");
*-%>
<% if (weeklyFile) { %>[[<% weeklyFile.basename %>]]<% } %> - This Week's Plan
<% if (monthlyFile) { %>[[<% monthlyFile.basename %>]]<% } %> - This Month's Focus
- Yesterday: [[<% yesterday %>]]
- Tomorrow: [[<% tomorrow %>]]

---

*Day <% dayOfYear %> of 365*
*Week <% weekNum %> of 52*

**Today's Affirmation:** Focus on progress, not perfection. Each small step counts.
