Checklist בדיקות, שמטרתה:
	•	לתקן RTL/LTR
	•	ליישר טקסט לפי שפה
	•	להתאים Wizard + Stepper
	•	למנוע בעיות Mobile
	•	לעבוד נכון עם Livewire 4 + Tailwind

TASK: Audit and fix RTL/LTR UI behavior in the existing Laravel + Livewire 4 application.

GOAL
Ensure the UI correctly supports both Hebrew (RTL) and English (LTR) including:
- text alignment
- wizard stepper direction
- input alignment
- button order
- responsive mobile layout

The system must dynamically adapt based on the current language.

LANGUAGES
Hebrew → RTL
English → LTR

The UI must automatically adjust when the locale changes.


⸻

שלב 1 – זיהוי השפה

בדוק כיצד נקבעת השפה.

חפש:

app()->getLocale()

או

config('app.locale')

אם אין לוגיקה קיימת, הוסף helper:

function isRTL()
{
    return app()->getLocale() === 'he';
}


⸻

שלב 2 – הגדרת direction גלובלי

בדוק את ה-layout הראשי:

resources/views/layouts/app.blade.php

ודא שקיים:

<html lang="{{ app()->getLocale() }}"
      dir="{{ app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}">

או:

<body dir="{{ app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}">

זה הבסיס לכל RTL.

⸻

שלב 3 – התאמת Tailwind ל-RTL

בדוק האם יש שימוש ב-classes שתלויות כיוון:

בעייתי:

text-left
ml-4
mr-2

יש להחליף ל-logical utilities:

text-start
ms-4
me-2

כך שהקוד יעבוד גם RTL וגם LTR.

⸻

שלב 4 – תיקון Stepper / Wizard

ב-wizard stepper:

בדוק אם יש:

flex
justify-between

יש להפוך את הסדר לפי השפה.

פתרון:

<div class="flex {{ app()->getLocale() === 'he' ? 'flex-row-reverse' : 'flex-row' }}">

או Tailwind RTL plugin.

⸻

שלב 5 – כפתורי ניווט Wizard

בדוק את סדר הכפתורים:

LTR:

Previous  |  Next

RTL:

Next  |  Previous

פתרון:

<div class="flex {{ app()->getLocale() === 'he' ? 'flex-row-reverse' : '' }} gap-4">


⸻

שלב 6 – יישור Inputs

בדוק inputs:

בעייתי:

text-left

פתרון:

text-start

או:

<input class="text-{{ app()->getLocale() === 'he' ? 'right' : 'left' }}">


⸻

שלב 7 – Select dropdown direction

Select לעיתים נשאר LTR.

פתרון:

select {
    direction: inherit;
}

או:

<select dir="{{ app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}">


⸻

שלב 8 – Placeholder alignment

בדוק:

Account display name
שם

placeholder צריך להיות מיושר לשפה.

פתרון:

::placeholder {
    text-align: start;
}


⸻

שלב 9 – מניעת rerender מיותר ב-Livewire Wizard

בדוק אם כל step גורם ל-render מלא.

מומלץ:

wire:model.defer

או

wire:model.lazy

ולא:

wire:model

כדי למנוע request על כל הקלדה.

⸻

שלב 10 – בדיקות Mobile

בדוק:
	•	padding
	•	width
	•	stepper wrapping

לדוגמה:

max-w-md
mx-auto
px-4

ל-mobile container.

⸻

CHECKLIST בדיקות

Agent חייב לאשר שכל הסעיפים עברו:

Global
	•	html כולל dir=rtl/ltr
	•	locale מזוהה דרך app()->getLocale()

Layout
	•	אין שימוש ב-text-left
	•	אין שימוש ב-ml/mr
	•	משתמשים ב-text-start
	•	משתמשים ב-ms/me

Wizard
	•	stepper מתהפך ב-RTL
	•	סדר steps תקין
	•	spacing אחיד

Buttons
	•	סדר next/previous מותאם לשפה
	•	כיוון flex נכון

Inputs
	•	text alignment לפי שפה
	•	placeholder alignment
	•	select dropdown direction

Mobile
	•	stepper לא נשבר במסך קטן
	•	inputs ברוחב נכון
	•	buttons לא יוצאים מהמסך

Livewire
	•	שימוש ב-wire:model.defer
	•	אין rerender מיותר
	•	state נשמר בין steps

⸻

תוצאה מצופה

לאחר היישום:

Hebrew UI

3 ← 2 ← 1

English UI

1 → 2 → 3

Inputs מיושרים נכון
כפתורים בסדר נכון
Stepper נראה תקין במובייל.

⸻

