---
date: 2026-03-16
tags: [knowledge, excalidraw, diagrams, plugin]
status: active
---

# Excalidraw Plugin — מדריך ואינדקס

> **Plugin:** `obsidian-excalidraw-plugin` by Zsolt Viczian  
> **Docs:** https://excalidraw-obsidian.online/wiki/welcome  
> **GitHub:** https://github.com/zsviczian/obsidian-excalidraw-plugin  
> **API Docs:** https://zsviczian.github.io/obsidian-excalidraw-plugin/

---

## מה זה Excalidraw?

כלי ציור וסכמות המשובץ ישירות ב-Obsidian. קבצים מאוחסנים כ-`.excalidraw.md` — פורמט JSON עם תמיכה מלאה ב-wiki-links, embed, ו-backlinks.

**שימושים ב-KALFA vault:**
- דיאגרמות ארכיטקטורה (service flow, system layers)
- סכמות DB (ERD — Entity Relationship Diagrams)
- תהליכי RSVP, Billing, Permissions
- תרשימי זרימה (API flows, webhook lifecycle)
- סקיצות מפגשים וסיעור מוחות

---

## קבצי ציור בוולט

| קובץ | תיאור |
|------|-------|
| [[Excalidraw/Drawing 2026-03-12 08.03.48.excalidraw]] | ציור 12/3 — בוקר |
| [[Excalidraw/Drawing 2026-03-12 11.49.56.excalidraw]] | ציור 12/3 — 11:49 |
| [[Excalidraw/Drawing 2026-03-12 11.55.20.excalidraw]] | ציור 12/3 — 11:55 |
| [[Excalidraw/Drawing 2026-03-12 11.56.43.excalidraw]] | ציור 12/3 — 11:56 |
| [[Excalidraw/Drawing 2026-03-12 12.02.51.excalidraw]] | ציור 12/3 — 12:02 |
| [[Excalidraw/Drawing 2026-03-12 12.02.57.excalidraw]] | ציור 12/3 — 12:02 |
| [[Excalidraw/Drawing 2026-03-12 12.12.40.excalidraw]] | ציור 12/3 — 12:12 |
| [[Excalidraw/Drawing 2026-03-16 08.32.31.excalidraw]] | ציור 16/3 — 08:32 |
| [[Excalidraw/Drawing 2026-03-16 08.32.33.excalidraw]] | ציור 16/3 — 08:32 |

> 💡 **טיפ:** שנה שמות לציורים לשמות תיאוריים כמו `Architecture-System-Overview.excalidraw.md`

---

## סקריפטים מותקנים

הסקריפטים נמצאים ב-`Excalidraw/Scripts/Downloaded/` וזמינים דרך **Command Palette**.

| סקריפט | קיצור פעולה | תיאור |
|--------|-------------|-------|
| [[Excalidraw/Scripts/Downloaded/Auto Layout]] | `Excalidraw: Auto Layout` | פריסה אוטומטית של אלמנטים עם elkjs (דורש אינטרנט). תומך ב-layered, radial, tree |
| [[Excalidraw/Scripts/Downloaded/Connect elements]] | `Excalidraw: Connect elements` | חיבור 2 אלמנטים נבחרים בחץ |
| [[Excalidraw/Scripts/Downloaded/Boolean Operations]] | `Excalidraw: Boolean Operations` | Union / Intersection / Difference על צורות |
| [[Excalidraw/Scripts/Downloaded/Box Selected Elements]] | `Excalidraw: Box Selected Elements` | הוספת מלבן סביב אלמנטים נבחרים |
| [[Excalidraw/Scripts/Downloaded/Box Each Selected Groups]] | `Excalidraw: Box Each Selected Groups` | מלבן סביב כל קבוצה נבחרת בנפרד |
| [[Excalidraw/Scripts/Downloaded/Concatenate lines]] | `Excalidraw: Concatenate lines` | מיזוג קווים לקו אחד רציף |
| [[Excalidraw/Scripts/Downloaded/Add Next Step in Process]] | `Excalidraw: Add Next Step in Process` | הוספת שלב הבא בתרשים זרימה |
| [[Excalidraw/Scripts/Downloaded/Copy Selected Element Styles to Global]] | `Excalidraw: Copy Element Styles` | העתקת סגנון אלמנט לברירת מחדל גלובלית |
| [[Excalidraw/Scripts/Downloaded/Folder Note Core - Make Current Drawing a Folder]] | `Excalidraw: Make Drawing a Folder` | הפיכת ציור ל-Folder Note |
| [[Excalidraw/Scripts/Downloaded/Full-Year Calendar Generator]] | `Excalidraw: Full-Year Calendar` | יצירת לוח שנה שנתי בציור |

---

## יכולות מרכזיות

### 📎 Embedding בנוטים

```markdown
![[My Diagram.excalidraw]]
![[My Diagram.excalidraw|400]]
![[My Diagram.excalidraw|400x300]]
```

ניתן להגדיר רוחב/גובה. הציור מתעדכן בזמן אמת.

### 🔗 Wiki-links בתוך ציורים

בתוך Excalidraw אפשר ליצור **text element** עם `[[Note Name]]` — לחיצה תנווט לנוט.

### 📤 Export

- **PNG / SVG** — אוטומטי (auto-export) לצד קובץ `.excalidraw.md`
- הגדרת תיקיית export: Settings → Excalidraw → Auto-export
- Keep in sync: PNG/SVG מתעדכנים בכל שמירה

### 🎨 Templates

1. צור ציור בסיסי עם עיצוב מועדף
2. שמור ב-`Templates/`
3. Settings → Excalidraw → Template file → הצבע על הקובץ
4. כל ציור חדש יפתח עם התבנית

### ✏️ Markdown mode

כל `.excalidraw.md` ניתן לפתיחה כ-Markdown — רואים את ה-JSON הגולמי. שימושי לבדיקת links.

---

## ExcalidrawAutomate API — סקריפטינג

ה-API מאפשר יצירה פרוגרמטית של ציורים מ-JavaScript/Templater:

```javascript
const ea = ExcalidrawAutomate;
ea.reset();

// יצירת מלבן
ea.addRect(100, 100, 200, 80);
// יצירת טקסט
ea.addText(110, 130, "My Service");
// יצירת חץ בין שני אלמנטים
const id1 = ea.addRect(0, 0, 120, 60);
const id2 = ea.addRect(200, 0, 120, 60);
await ea.addArrow([{ id: id1 }, { id: id2 }]);

// שמירה לקובץ
await ea.create({ filename: "My Diagram", foldername: "Architecture/" });
```

**שימושים מעשיים:**
- יצירת ERD אוטומטי מ-Dataview query
- דיאגרמת services מ-YAML
- mindmap מ-רשימות markdown

---

## קיצורי מקלדת בסיסיים

| פעולה | מקש |
|-------|-----|
| New drawing | `Ctrl+Shift+E` (ניתן להגדרה) |
| Select all | `Ctrl+A` |
| Group selected | `Ctrl+G` |
| Ungroup | `Ctrl+Shift+G` |
| Duplicate | `Ctrl+D` |
| Flip horizontal | `Shift+H` |
| Flip vertical | `Shift+V` |
| Zoom to fit | `Shift+1` |
| Add link to element | `Ctrl+K` |
| Command palette (scripts) | `Ctrl+P` |

---

## הגדרות מומלצות ל-KALFA

```
Excalidraw folder:       Excalidraw/
Template file:           (לאחר יצירת תבנית)
Auto-export SVG:         ON
Auto-export PNG:         OFF
Default filename prefix: Drawing
Attach to active:        ON
Wiki-links in drawings:  ON
```

---

## Configuration Guide המורחב

לפירוט מלא של כל הגדרות הפלאגין ראה:
[[Knowledge/Excalidraw Configuration Guide]]

---

## משאבים חיצוניים

- 📖 [Wiki רשמי](https://excalidraw-obsidian.online/wiki/welcome)
- 📚 [Script Library](https://github.com/zsviczian/obsidian-excalidraw-plugin/wiki/Excalidraw-Script-Engine-scripts-library)
- 🎥 [57 Features in 17 Minutes](https://bagrounds.org/videos/the-excalidraw-obsidian-showcase-57-key-features-in-just-17-minutes)
- 🔧 [ExcalidrawAutomate API](https://zsviczian.github.io/obsidian-excalidraw-plugin/)
- 🐙 [GitHub](https://github.com/zsviczian/obsidian-excalidraw-plugin)

---

## Related

- [[Architecture/README|Architecture Documentation]] — הציורים עוזרים לתעד ארכיטקטורה
- [[Knowledge/README|Knowledge Base]]
