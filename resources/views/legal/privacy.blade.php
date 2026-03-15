<x-guest-layout>
    <x-slot:title>מדיניות פרטיות – {{ config('app.name') }}</x-slot:title>

    <div class="max-w-3xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-brand font-bold text-lg mb-6">
                {{ config('app.name') }}
            </a>
            <h1 class="text-3xl font-black text-content mb-2">מדיניות פרטיות</h1>
            <p class="text-sm text-content-muted">עדכון אחרון: מרץ 2026 | גרסה 1.0 | בהתאם לחוק הגנת הפרטיות ותיקון 13 (2024)</p>
        </div>

        {{-- Notice box --}}
        <div class="bg-brand/5 border border-brand/20 rounded-2xl p-4 mb-8 text-sm text-content">
            <p><strong>תמצית:</strong> אנו אוספים רק את המידע הנדרש לצורך מתן השירות. איננו מוכרים את מידעך לצדדים שלישיים. אתה שולט במידע שלך ורשאי לדרוש את מחיקתו בכל עת.</p>
        </div>

        {{-- Table of contents --}}
        <div class="card p-6 mb-8 text-sm">
            <p class="font-bold text-content mb-3">תוכן עניינים</p>
            <ol class="list-decimal list-inside space-y-1 text-content-muted">
                <li><a href="#controller" class="text-brand hover:underline">מי אחראי על המידע</a></li>
                <li><a href="#collected" class="text-brand hover:underline">מידע שאנו אוספים</a></li>
                <li><a href="#purpose" class="text-brand hover:underline">מטרות עיבוד המידע</a></li>
                <li><a href="#legal-basis" class="text-brand hover:underline">בסיס משפטי לעיבוד</a></li>
                <li><a href="#retention" class="text-brand hover:underline">שמירת מידע</a></li>
                <li><a href="#sharing" class="text-brand hover:underline">שיתוף מידע עם צדדים שלישיים</a></li>
                <li><a href="#rights" class="text-brand hover:underline">זכויותיך</a></li>
                <li><a href="#security" class="text-brand hover:underline">אבטחת מידע</a></li>
                <li><a href="#cookies" class="text-brand hover:underline">עוגיות (Cookies)</a></li>
                <li><a href="#transfers" class="text-brand hover:underline">העברת מידע לחו"ל</a></li>
                <li><a href="#children" class="text-brand hover:underline">מידע על קטינים</a></li>
                <li><a href="#contact" class="text-brand hover:underline">יצירת קשר ותלונות</a></li>
            </ol>
        </div>

        <div class="space-y-8 text-content leading-relaxed text-sm">

            <section id="controller">
                <h2 class="text-xl font-black text-content mb-3">1. מי אחראי על המידע (Data Controller)</h2>
                <p>{{ config('app.name') }} (להלן: "אנחנו" או "החברה") היא הגוף האחראי (Controller) לעיבוד המידע האישי שנאסף במסגרת שירות kalfa.me. לפניות בנושא פרטיות:</p>
                <div class="card-surface p-4 mt-3 inline-block">
                    <p class="font-bold text-content">{{ config('app.name') }}</p>
                    <p class="text-content-muted">דוא"ל: <a href="mailto:privacy@kalfa.me" class="text-brand hover:underline">privacy@kalfa.me</a></p>
                </div>
            </section>

            <section id="collected">
                <h2 class="text-xl font-black text-content mb-3">2. מידע שאנו אוספים</h2>

                <p class="font-semibold text-content mb-2">2.1 מידע שאתה מספק ישירות:</p>
                <ul class="list-disc list-inside space-y-1 text-content-muted mb-4">
                    <li>שם מלא וכתובת דוא"ל (הרשמה)</li>
                    <li>מספר טלפון (לאימות OTP ושירותי קול)</li>
                    <li>שם ארגון ופרטי קשר</li>
                    <li>פרטי אירועים: שם, תאריך, מיקום, פרטי אורחים</li>
                    <li>אמצעי תשלום (דרך שער תשלומים מאובטח — איננו שומרים פרטי כרטיס אשראי ישירות)</li>
                </ul>

                <p class="font-semibold text-content mb-2">2.2 מידע שנאסף אוטומטית:</p>
                <ul class="list-disc list-inside space-y-1 text-content-muted mb-4">
                    <li>כתובת IP ונתוני גישה לשרת</li>
                    <li>סוג דפדפן ומערכת הפעלה</li>
                    <li>נתוני שימוש בשירות (דפים שנצפו, פעולות שבוצעו)</li>
                    <li>עוגיות ומזהי session</li>
                </ul>

                <p class="font-semibold text-content mb-2">2.3 מידע שמתקבל מאורחי אירועים:</p>
                <ul class="list-disc list-inside space-y-1 text-content-muted">
                    <li>שם אורח, טלפון, אישורי הגעה (RSVP)</li>
                    <li>תגובות לשאלונים באירוע</li>
                    <li>מספר מלווים</li>
                </ul>
            </section>

            <section id="purpose">
                <h2 class="text-xl font-black text-content mb-3">3. מטרות עיבוד המידע</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-stroke rounded-xl overflow-hidden">
                        <thead class="bg-surface">
                            <tr>
                                <th class="text-start p-3 font-semibold text-content border-b border-stroke">מטרה</th>
                                <th class="text-start p-3 font-semibold text-content border-b border-stroke">סוג מידע</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stroke">
                            <tr><td class="p-3">מתן השירות ותפעולו</td><td class="p-3 text-content-muted">חשבון, ארגון, אירועים</td></tr>
                            <tr><td class="p-3">עיבוד תשלומים</td><td class="p-3 text-content-muted">פרטי חיוב, מזהה עסקה</td></tr>
                            <tr><td class="p-3">שליחת הזמנות ואישורי RSVP</td><td class="p-3 text-content-muted">שם, טלפון, דוא"ל אורח</td></tr>
                            <tr><td class="p-3">שיחות קוליות לאישור הגעה (Voice RSVP)</td><td class="p-3 text-content-muted">מספר טלפון, תגובה קולית</td></tr>
                            <tr><td class="p-3">תמיכה טכנית ושירות לקוחות</td><td class="p-3 text-content-muted">פרטי חשבון, תיאור בעיה</td></tr>
                            <tr><td class="p-3">אבטחת מידע ומניעת הונאות</td><td class="p-3 text-content-muted">IP, לוגים, session</td></tr>
                            <tr><td class="p-3">עמידה בחוק ורגולציה</td><td class="p-3 text-content-muted">כל הנדרש על פי חוק</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="legal-basis">
                <h2 class="text-xl font-black text-content mb-3">4. בסיס משפטי לעיבוד (תיקון 13 לחוק הגנת הפרטיות)</h2>
                <ul class="list-disc list-inside space-y-2 text-content-muted">
                    <li><strong class="text-content">הסכמה:</strong> עיבוד לצרכי שיווק, הודעות אופציונליות, קבלת ניוזלטר</li>
                    <li><strong class="text-content">ביצוע חוזה:</strong> מתן השירות, עיבוד תשלומים, שליחת הזמנות</li>
                    <li><strong class="text-content">עמידה בחוק:</strong> שמירת רשומות חשבונאיות, מניעת הלבנת הון</li>
                    <li><strong class="text-content">אינטרס לגיטימי:</strong> אבטחת מידע, מניעת הונאות, שיפור השירות</li>
                </ul>
            </section>

            <section id="retention">
                <h2 class="text-xl font-black text-content mb-3">5. שמירת מידע</h2>
                <ul class="list-disc list-inside space-y-1 text-content-muted">
                    <li>נתוני חשבון: נשמרים כל עוד החשבון פעיל + 30 יום לאחר מחיקה</li>
                    <li>נתוני אירועים ואורחים: 3 שנים ממועד האירוע (לצרכי שירות לקוחות)</li>
                    <li>רשומות תשלום: 7 שנים (דרישת חוק חשבונאות ישראלי)</li>
                    <li>לוגי מערכת: 90 יום</li>
                    <li>עוגיות: בהתאם לסוג (ראה סעיף 9)</li>
                </ul>
            </section>

            <section id="sharing">
                <h2 class="text-xl font-black text-content mb-3">6. שיתוף מידע עם צדדים שלישיים</h2>
                <p class="mb-3">אנו <strong>אינו מוכרים</strong> את מידעך. מידע משותף אך ורק עם הגורמים הבאים לצורך מתן השירות:</p>
                <ul class="list-disc list-inside space-y-2 text-content-muted">
                    <li><strong class="text-content">SUMIT (עיבוד תשלומים):</strong> פרטי חיוב לצורך ביצוע עסקאות</li>
                    <li><strong class="text-content">Twilio (תקשורת):</strong> מספרי טלפון לשליחת SMS, WhatsApp ושיחות קוליות</li>
                    <li><strong class="text-content">Google (בינה מלאכותית):</strong> שירות Gemini Live לעיבוד שיחות RSVP קוליות</li>
                    <li><strong class="text-content">ספקי ענן (אחסון):</strong> שרתים מאובטחים לאחסון הנתונים</li>
                    <li><strong class="text-content">רשויות חוק:</strong> כאשר נדרש על פי צו שיפוטי או חוק</li>
                </ul>
            </section>

            <section id="rights">
                <h2 class="text-xl font-black text-content mb-3">7. זכויותיך (חוק הגנת הפרטיות, תיקון 13)</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach([
                        ['icon' => '👁️', 'title' => 'זכות גישה', 'desc' => 'לדעת איזה מידע אנו מחזיקים עליך'],
                        ['icon' => '✏️', 'title' => 'זכות תיקון', 'desc' => 'לתקן מידע לא מדויק'],
                        ['icon' => '🗑️', 'title' => 'זכות מחיקה', 'desc' => 'לבקש מחיקת מידעך ("הזכות להישכח")'],
                        ['icon' => '📦', 'title' => 'ניידות מידע', 'desc' => 'לקבל את מידעך בפורמט מובנה'],
                        ['icon' => '🚫', 'title' => 'זכות התנגדות', 'desc' => 'להתנגד לעיבוד מסוים של מידעך'],
                        ['icon' => '↩️', 'title' => 'שלילת הסכמה', 'desc' => 'לשלול הסכמה שנתת בכל עת'],
                    ] as $right)
                    <div class="card-surface p-3 flex gap-3 items-start">
                        <span class="text-xl">{{ $right['icon'] }}</span>
                        <div>
                            <p class="font-semibold text-content text-sm">{{ $right['title'] }}</p>
                            <p class="text-content-muted text-xs">{{ $right['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <p class="mt-4 text-content-muted">לממש את זכויותיך, פנה אלינו בדוא"ל <a href="mailto:privacy@kalfa.me" class="text-brand hover:underline">privacy@kalfa.me</a>. נשיב תוך 30 יום.</p>
            </section>

            <section id="security">
                <h2 class="text-xl font-black text-content mb-3">8. אבטחת מידע</h2>
                <ul class="list-disc list-inside space-y-1 text-content-muted">
                    <li>הצפנת TLS/HTTPS לכל התקשורת</li>
                    <li>הצפנת מסד נתונים ב-rest</li>
                    <li>בקרות גישה מבוססות תפקידים (RBAC)</li>
                    <li>אימות דו-שלבי (OTP/Passkeys)</li>
                    <li>ניטור ולוגים לזיהוי חריגות</li>
                    <li>בדיקות אבטחה תקופתיות</li>
                </ul>
                <p class="mt-3">במקרה של אירוע אבטחה שעלול לפגוע במידעך, נודיע לך ולרשות הגנת הפרטיות (PPA) בהתאם לחוק.</p>
            </section>

            <section id="cookies">
                <h2 class="text-xl font-black text-content mb-3">9. עוגיות (Cookies)</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-stroke rounded-xl overflow-hidden">
                        <thead class="bg-surface">
                            <tr>
                                <th class="text-start p-3 font-semibold text-content border-b border-stroke">סוג</th>
                                <th class="text-start p-3 font-semibold text-content border-b border-stroke">מטרה</th>
                                <th class="text-start p-3 font-semibold text-content border-b border-stroke">תוקף</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stroke">
                            <tr><td class="p-3">Session</td><td class="p-3 text-content-muted">שמירת מצב התחברות</td><td class="p-3 text-content-muted">עד סגירת דפדפן</td></tr>
                            <tr><td class="p-3">CSRF Token</td><td class="p-3 text-content-muted">הגנה מפני זיוף בקשות</td><td class="p-3 text-content-muted">2 שעות</td></tr>
                            <tr><td class="p-3">Theme</td><td class="p-3 text-content-muted">זיכרון העדפת מצב כהה/בהיר</td><td class="p-3 text-content-muted">שנה</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-content-muted">ניתן לחסום עוגיות בהגדרות הדפדפן שלך. חסימת עוגיות session עלולה לפגוע בתפקוד השירות.</p>
            </section>

            <section id="transfers">
                <h2 class="text-xl font-black text-content mb-3">10. העברת מידע לחו"ל</h2>
                <p>חלק מהשירותים שלנו (Twilio, Google) מעבדים נתונים בשרתים מחוץ לישראל. העברות אלה מתבצעות בהתאם לאמצעי הגנה מתאימים:</p>
                <ul class="list-disc list-inside space-y-1 text-content-muted mt-2">
                    <li>חוזים עם ספקים הכוללים סעיפי הגנה סטנדרטיים (SCCs)</li>
                    <li>שימוש בספקים עם הסמכת adequacy (ארה"ב – EU-US Data Privacy Framework)</li>
                </ul>
            </section>

            <section id="children">
                <h2 class="text-xl font-black text-content mb-3">11. מידע על קטינים</h2>
                <p>השירות אינו מיועד לילדים מתחת לגיל 18. איננו אוספים ביודעין מידע מקטינים. אם נודע לנו שנאסף מידע כזה שלא כדין, נמחק אותו מיידית.</p>
            </section>

            <section id="contact">
                <h2 class="text-xl font-black text-content mb-3">12. יצירת קשר ותלונות</h2>
                <p>לשאלות, בקשות מימוש זכויות, או תלונות:</p>
                <div class="card-surface p-4 mt-3 inline-block">
                    <p class="font-bold text-content">{{ config('app.name') }} — פרטיות</p>
                    <p class="text-content-muted text-sm">דוא"ל: <a href="mailto:privacy@kalfa.me" class="text-brand hover:underline">privacy@kalfa.me</a></p>
                </div>
                <p class="mt-4 text-content-muted">אם לא קיבלת מענה מספק, יש לך הזכות לפנות לרשות להגנת הפרטיות (PPA) בכתובת <a href="https://www.gov.il/he/departments/ministry_of_justice/govil-landing-page" target="_blank" rel="noopener" class="text-brand hover:underline">gov.il</a>.</p>
            </section>

        </div>

        {{-- Footer --}}
        <div class="mt-12 pt-6 border-t border-stroke flex flex-wrap gap-4 text-sm text-content-muted justify-center">
            <span>© {{ date('Y') }} {{ config('app.name') }}. כל הזכויות שמורות.</span>
            <a href="{{ route('terms') }}" class="text-brand hover:underline">תנאי שימוש</a>
            <a href="{{ route('refund.policy') }}" class="text-brand hover:underline">מדיניות ביטולים</a>
        </div>

    </div>
</x-guest-layout>
