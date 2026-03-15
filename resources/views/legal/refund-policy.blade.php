<x-guest-layout>
    <x-slot:title>מדיניות ביטולים והחזרות – {{ config('app.name') }}</x-slot:title>

    <div class="max-w-3xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-brand font-bold text-lg mb-6">
                {{ config('app.name') }}
            </a>
            <h1 class="text-3xl font-black text-content mb-2">מדיניות ביטולים והחזרות</h1>
            <p class="text-sm text-content-muted">עדכון אחרון: מרץ 2026 | גרסה 1.0</p>
        </div>

        {{-- Notice --}}
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-8 text-sm text-amber-800">
            <p><strong>שים לב:</strong> מדיניות זו חלה על תשלומי פלטפורמת kalfa.me בלבד. מדיניות הביטול לאורחי האירוע עצמו נקבעת על ידי מארגן האירוע.</p>
        </div>

        {{-- Table of contents --}}
        <div class="card p-6 mb-8 text-sm">
            <p class="font-bold text-content mb-3">תוכן עניינים</p>
            <ol class="list-decimal list-inside space-y-1 text-content-muted">
                <li><a href="#event-billing" class="text-brand hover:underline">ביטול תשלום אירוע</a></li>
                <li><a href="#subscription" class="text-brand hover:underline">ביטול מנוי</a></li>
                <li><a href="#process" class="text-brand hover:underline">תהליך בקשת החזר</a></li>
                <li><a href="#timeline" class="text-brand hover:underline">לוחות זמנים</a></li>
                <li><a href="#exceptions" class="text-brand hover:underline">חריגים ומקרים מיוחדים</a></li>
                <li><a href="#disputes" class="text-brand hover:underline">סכסוכים וחיובים חוזרים</a></li>
                <li><a href="#contact" class="text-brand hover:underline">יצירת קשר</a></li>
            </ol>
        </div>

        <div class="space-y-8 text-content leading-relaxed text-sm">

            <section id="event-billing">
                <h2 class="text-xl font-black text-content mb-3">1. ביטול תשלום אירוע</h2>
                <p class="mb-3">תשלום אירוע הוא דמי הפעלה חד-פעמיים עבור פרסום וניהול אירוע מסוים בפלטפורמה.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-stroke rounded-xl overflow-hidden">
                        <thead class="bg-surface">
                            <tr>
                                <th class="text-start p-3 font-semibold text-content border-b border-stroke">מועד הביטול</th>
                                <th class="text-start p-3 font-semibold text-content border-b border-stroke">זכאות להחזר</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stroke">
                            <tr>
                                <td class="p-3">תוך 24 שעות מרגע התשלום (לפני שידורי הזמנות)</td>
                                <td class="p-3"><span class="badge-success">החזר מלא 100%</span></td>
                            </tr>
                            <tr>
                                <td class="p-3">24 שעות – 7 ימים (לפני מועד האירוע)</td>
                                <td class="p-3"><span class="badge-warning">החזר חלקי 50%</span></td>
                            </tr>
                            <tr>
                                <td class="p-3">פחות מ-7 ימים לפני האירוע</td>
                                <td class="p-3"><span class="badge-danger">ללא החזר</span></td>
                            </tr>
                            <tr>
                                <td class="p-3">לאחר האירוע</td>
                                <td class="p-3"><span class="badge-danger">ללא החזר</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-content-muted">* "לפני שידורי הזמנות" — לפני שנשלחה הזמנה כלשהי לאורח.</p>
            </section>

            <section id="subscription">
                <h2 class="text-xl font-black text-content mb-3">2. ביטול מנוי</h2>
                <p><strong>2.1 ביטול עתידי:</strong> ניתן לבטל מנוי בכל עת מהגדרות החשבון. הגישה לשירות תישאר פעילה עד תום תקופת החיוב הנוכחית.</p>
                <p class="mt-2"><strong>2.2 ביטול עם החזר (חוק הגנת הצרכן):</strong> בהתאם לחוק הגנת הצרכן הישראלי, ניתן לבטל עסקה תוך 14 יום מיום ההצטרפות ולקבל החזר מלא, בניכוי דמי ביטול של 5% או 100 ₪ (הנמוך מביניהם), אם לא נעשה שימוש בשירות.</p>
                <p class="mt-2"><strong>2.3 שדרוג תוכנית:</strong> בעת שדרוג מנוי, ההפרש מחויב באופן יחסי עבור שארית התקופה.</p>
                <p class="mt-2"><strong>2.4 שנמכוך תוכנית:</strong> בעת שנמכוך, השינוי ייכנס לתוקף בתחילת תקופת החיוב הבאה.</p>
            </section>

            <section id="process">
                <h2 class="text-xl font-black text-content mb-3">3. תהליך בקשת החזר</h2>
                <ol class="list-decimal list-inside space-y-3 text-content-muted">
                    <li>
                        <strong class="text-content">שלח בקשה:</strong> שלח דוא"ל ל-<a href="mailto:billing@kalfa.me" class="text-brand hover:underline">billing@kalfa.me</a> עם:
                        <ul class="list-disc list-inside mt-1 ms-4 space-y-0.5">
                            <li>שם חשבונך ודוא"ל רשום</li>
                            <li>מספר הזמנה או מזהה עסקה</li>
                            <li>סיבת הביטול</li>
                        </ul>
                    </li>
                    <li><strong class="text-content">אישור:</strong> תקבל אישור על קבלת הבקשה תוך 24 שעות.</li>
                    <li><strong class="text-content">בדיקה:</strong> הצוות שלנו יבדוק את הבקשה תוך 3 ימי עסקים.</li>
                    <li><strong class="text-content">ביצוע החזר:</strong> לאחר אישור, ההחזר יועבר לאמצעי התשלום המקורי.</li>
                </ol>
            </section>

            <section id="timeline">
                <h2 class="text-xl font-black text-content mb-3">4. לוחות זמנים להחזר</h2>
                <ul class="list-disc list-inside space-y-2 text-content-muted">
                    <li><strong class="text-content">כרטיס אשראי:</strong> 5–10 ימי עסקים (תלוי בחברת האשראי)</li>
                    <li><strong class="text-content">העברה בנקאית:</strong> 3–5 ימי עסקים</li>
                    <li><strong class="text-content">זיכוי לחשבון הפלטפורמה:</strong> מיידי (לשימוש עתידי בפלטפורמה)</li>
                </ul>
                <p class="mt-3 text-content-muted">לוחות הזמנים הינם תוצאה של עיבוד שער התשלומים ואינם בשליטתנו המלאה.</p>
            </section>

            <section id="exceptions">
                <h2 class="text-xl font-black text-content mb-3">5. חריגים ומקרים מיוחדים</h2>

                <p class="font-semibold text-content mb-2">5.1 תקלות טכניות:</p>
                <p class="text-content-muted">אם השירות לא פעל כראוי עקב תקלה מצידנו, תהיה זכאי להחזר מלא ללא קשר לתקופה. יש לדווח על תקלה תוך 48 שעות מרגע גילויה.</p>

                <p class="font-semibold text-content mt-4 mb-2">5.2 נסיבות חריגות:</p>
                <p class="text-content-muted">במקרים כגון מוות, אשפוז, אסון טבע — ניתן להגיש בקשה לחריגה ממדיניות זו עם מסמכים תומכים. כל מקרה יישקל לגופו.</p>

                <p class="font-semibold text-content mt-4 mb-2">5.3 ללא זכאות להחזר:</p>
                <ul class="list-disc list-inside space-y-1 text-content-muted">
                    <li>ביטול עקב הפרת תנאי שימוש</li>
                    <li>תשלום שבוצע בכוונת מרמה</li>
                    <li>שינויים בתוכן האירוע לאחר פרסום</li>
                    <li>בקשה לאחר פירוק הארגון</li>
                </ul>
            </section>

            <section id="disputes">
                <h2 class="text-xl font-black text-content mb-3">6. סכסוכים וחיובים חוזרים (Chargebacks)</h2>
                <p>אנא פנה אלינו ישירות לפני פתיחת חיוב חוזר (chargeback) עם חברת האשראי. אנו מתחייבים להגיב תוך 48 שעות לכל פנייה.</p>
                <p class="mt-2 text-content-muted">פתיחת chargeback ללא פנייה מוקדמת אלינו עלולה לגרום לחסימת החשבון. אם הבעיה לא נפתרה אחרי פנייה ישירה, תוכל לפנות לחברת האשראי שלך.</p>
            </section>

            <section id="contact">
                <h2 class="text-xl font-black text-content mb-3">7. יצירת קשר</h2>
                <p>לשאלות בנושא חיוב, ביטולים והחזרים:</p>
                <div class="card-surface p-4 mt-3 inline-block">
                    <p class="font-bold text-content">{{ config('app.name') }} — צוות חיוב</p>
                    <p class="text-content-muted text-sm">דוא"ל: <a href="mailto:billing@kalfa.me" class="text-brand hover:underline">billing@kalfa.me</a></p>
                    <p class="text-content-muted text-sm">שעות פעילות: א'–ה', 09:00–18:00</p>
                </div>
            </section>

        </div>

        {{-- Footer --}}
        <div class="mt-12 pt-6 border-t border-stroke flex flex-wrap gap-4 text-sm text-content-muted justify-center">
            <span>© {{ date('Y') }} {{ config('app.name') }}. כל הזכויות שמורות.</span>
            <a href="{{ route('terms') }}" class="text-brand hover:underline">תנאי שימוש</a>
            <a href="{{ route('privacy') }}" class="text-brand hover:underline">מדיניות פרטיות</a>
        </div>

    </div>
</x-guest-layout>
