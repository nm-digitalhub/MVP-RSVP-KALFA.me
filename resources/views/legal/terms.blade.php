<x-guest-layout>
    <x-slot:title>תנאי שימוש – {{ config('app.name') }}</x-slot:title>

    <div class="max-w-3xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-10">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-brand font-bold text-lg mb-6">
                {{ config('app.name') }}
            </a>
            <h1 class="text-3xl font-black text-content mb-2">תנאי שימוש</h1>
            <p class="text-sm text-content-muted">עדכון אחרון: מרץ 2026 | גרסה 1.0</p>
        </div>

        {{-- Table of contents --}}
        <div class="card p-6 mb-8 text-sm">
            <p class="font-bold text-content mb-3">תוכן עניינים</p>
            <ol class="list-decimal list-inside space-y-1 text-content-muted">
                <li><a href="#service" class="text-brand hover:underline">תיאור השירות</a></li>
                <li><a href="#account" class="text-brand hover:underline">הרשמה וחשבון משתמש</a></li>
                <li><a href="#billing" class="text-brand hover:underline">תשלום ומנויים</a></li>
                <li><a href="#use" class="text-brand hover:underline">כללי שימוש מותר</a></li>
                <li><a href="#ip" class="text-brand hover:underline">קניין רוחני</a></li>
                <li><a href="#liability" class="text-brand hover:underline">הגבלת אחריות</a></li>
                <li><a href="#termination" class="text-brand hover:underline">סיום והשהיית חשבון</a></li>
                <li><a href="#law" class="text-brand hover:underline">דין חל וסמכות שיפוט</a></li>
                <li><a href="#contact" class="text-brand hover:underline">יצירת קשר</a></li>
            </ol>
        </div>

        {{-- Prose content --}}
        <div class="prose prose-sm max-w-none space-y-8 text-content leading-relaxed">

            <section id="service">
                <h2 class="text-xl font-black text-content mb-3">1. תיאור השירות</h2>
                <p>{{ config('app.name') }} (להלן: "החברה" או "כלפה") מפעילה פלטפורמת ניהול אירועים, הזמנות וסידורי הושבה מבוסס ענן (להלן: "השירות"). השירות מאפשר לארגונים ועסקים לנהל אירועים, לשלוח הזמנות, לקבל אישורי הגעה ולנהל סידורי הושבה.</p>
                <p class="mt-2">השימוש בשירות מהווה הסכמה מלאה לתנאים אלה. אם אינך מסכים לתנאים, אנא הפסק את השימוש בשירות לאלתר.</p>
            </section>

            <section id="account">
                <h2 class="text-xl font-black text-content mb-3">2. הרשמה וחשבון משתמש</h2>
                <p><strong>2.1 זכאות:</strong> השירות מיועד לבעלי עסקים, ארגונים ומקצוענים שמלאו להם 18 שנה. שימוש בשירות מהווה הצהרה שאתה עומד בדרישות הזכאות.</p>
                <p class="mt-2"><strong>2.2 פרטי הרשמה:</strong> עליך לספק מידע מדויק ועדכני בעת ההרשמה. אתה אחראי לשמירת סודיות סיסמתך ולכל הפעולות המתבצעות תחת חשבונך.</p>
                <p class="mt-2"><strong>2.3 ארגונים:</strong> ניתן ליצור מספר ארגונים תחת חשבון אחד. בעלי ארגון אחראים לכל הפעולות המבוצעות תחת הארגון שלהם.</p>
                <p class="mt-2"><strong>2.4 אבטחה:</strong> יש להודיע לנו מיידית על כל שימוש לא מורשה בחשבונך. החברה אינה אחראית לנזקים שנגרמו עקב אי-דיווח על גישה לא מורשית.</p>
            </section>

            <section id="billing">
                <h2 class="text-xl font-black text-content mb-3">3. תשלום ומנויים</h2>
                <p><strong>3.1 תוכניות ותשלום:</strong> השירות מוצע בתוכניות שונות כמפורט בדף התמחור. כל תוכנית מגדירה את גבולות השימוש והתכונות הזמינות.</p>
                <p class="mt-2"><strong>3.2 חיוב:</strong> חיובים מעובדים דרך שער התשלומים של SUMIT בהתאם למדיניות המחירים הנוכחית. כל המחירים כוללים מע"מ אלא אם צוין אחרת.</p>
                <p class="mt-2"><strong>3.3 חידוש מנוי:</strong> מנויים מתחדשים אוטומטית אלא אם בוטלו לפחות 24 שעות לפני סיום תקופת החיוב.</p>
                <p class="mt-2"><strong>3.4 ביטולים והחזרים:</strong> ראה <a href="{{ route('refund.policy') }}" class="text-brand hover:underline">מדיניות ביטולים והחזרות</a> המלאה שלנו לפרטים.</p>
                <p class="mt-2"><strong>3.5 פיגור בתשלום:</strong> כשל בתשלום עשוי לגרום להשהיית גישה לשירות. החברה שומרת לעצמה הזכות לסגור חשבונות עם חובות פתוחים לאחר התראה.</p>
            </section>

            <section id="use">
                <h2 class="text-xl font-black text-content mb-3">4. כללי שימוש מותר</h2>
                <p class="mb-2">אתה מתחייב <strong>לא</strong> לעשות שימוש בשירות לצרכים הבאים:</p>
                <ul class="list-disc list-inside space-y-1 text-content-muted">
                    <li>הפרת חוק ישראלי או בינלאומי כלשהו</li>
                    <li>שליחת ספאם, הודעות פישינג או תקשורת מטעה</li>
                    <li>העלאת תוכן פוגעני, גזעני, מאיים או מטריד</li>
                    <li>ניסיון לפרוץ, לשבש או לנסות לקבל גישה לא מורשית למערכת</li>
                    <li>איסוף מידע על משתמשים אחרים ללא הסכמתם</li>
                    <li>העברה מסחרית חוזרת של השירות ללא אישור בכתב</li>
                    <li>הפרת פרטיות של אורחים ומשתתפי אירועים</li>
                </ul>
                <p class="mt-3">החברה שומרת לעצמה הזכות להשעות או לסגור חשבונות המפרים כללים אלה.</p>
            </section>

            <section id="ip">
                <h2 class="text-xl font-black text-content mb-3">5. קניין רוחני</h2>
                <p><strong>5.1 קניין החברה:</strong> כל הזכויות בשירות, כולל עיצוב, קוד, לוגו, סמלים, ותוכן תעודתי שייכים לחברה ומוגנים בחוקי זכויות יוצרים וקניין רוחני.</p>
                <p class="mt-2"><strong>5.2 רישיון שימוש:</strong> אנו מעניקים לך רישיון מוגבל, לא בלעדי, ובלתי ניתן להעברה לשימוש בשירות בהתאם לתנאים אלה.</p>
                <p class="mt-2"><strong>5.3 תוכן המשתמש:</strong> תוכן שאתה מעלה (רשימות אורחים, פרטי אירועים, תמונות) נשאר בבעלותך. אתה מעניק לחברה רישיון מוגבל לאחסן ולעבד תוכן זה לצורך מתן השירות בלבד.</p>
            </section>

            <section id="liability">
                <h2 class="text-xl font-black text-content mb-3">6. הגבלת אחריות</h2>
                <p><strong>6.1 זמינות שירות:</strong> החברה שואפת לזמינות של 99.5% אך אינה מתחייבת על שירות רציף וללא הפרעות.</p>
                <p class="mt-2"><strong>6.2 הגבלה כספית:</strong> סכום האחריות המקסימלי של החברה לא יעלה על הסכום ששולם על ידך ב-12 החודשים שקדמו לאירוע המקים את התביעה.</p>
                <p class="mt-2"><strong>6.3 נזקים עקיפים:</strong> החברה לא תהיה אחראית לנזקים עקיפים, מיוחדים, תוצאתיים או אובדן רווח.</p>
                <p class="mt-2"><strong>6.4 גיבוי נתונים:</strong> מומלץ שמירה עצמאית של גיבויים לנתוניך. החברה אינה אחראית לאבדן נתונים הנגרם מנסיבות שמחוץ לשליטתה הסבירה.</p>
            </section>

            <section id="termination">
                <h2 class="text-xl font-black text-content mb-3">7. סיום והשהיית חשבון</h2>
                <p><strong>7.1 ביטול על ידי המשתמש:</strong> ניתן לבטל את חשבונך בכל עת מתוך הגדרות החשבון. עם ביטול, הגישה לשירות תפסק בתום תקופת החיוב הנוכחית.</p>
                <p class="mt-2"><strong>7.2 סיום על ידי החברה:</strong> אנו שומרים לעצמנו הזכות להשעות או לסגור חשבונות עקב הפרת תנאים, אי-תשלום, שימוש לרעה, או פעילות בלתי חוקית, לאחר מתן הודעה סבירה.</p>
                <p class="mt-2"><strong>7.3 ייצוא נתונים:</strong> לאחר סיום החשבון, תינתן גישה לייצוא נתוניך למשך 30 יום. לאחר מכן יימחקו הנתונים לצמיתות.</p>
            </section>

            <section id="law">
                <h2 class="text-xl font-black text-content mb-3">8. דין חל וסמכות שיפוט</h2>
                <p>תנאים אלה כפופים לדיני מדינת ישראל. כל סכסוך הנוגע לתנאים אלה ייושב בבתי המשפט המוסמכים בתל-אביב-יפו, אלא אם קיים סעיף גישור מוסכם.</p>
                <p class="mt-2">אנו שואפים לפתור כל מחלוקת בדרך של משא ומתן ידידותי לפני פנייה לערכאות משפטיות. אנא פנה אלינו תחילה בכתב.</p>
            </section>

            <section id="contact">
                <h2 class="text-xl font-black text-content mb-3">9. יצירת קשר</h2>
                <p>לשאלות בנוגע לתנאי שימוש אלה:</p>
                <div class="card-surface p-4 mt-3 inline-block">
                    <p class="font-bold text-content">{{ config('app.name') }}</p>
                    <p class="text-content-muted text-sm">דוא"ל: <a href="mailto:legal@kalfa.me" class="text-brand hover:underline">legal@kalfa.me</a></p>
                </div>
            </section>

        </div>

        {{-- Footer links --}}
        <div class="mt-12 pt-6 border-t border-stroke flex flex-wrap gap-4 text-sm text-content-muted justify-center">
            <span>© {{ date('Y') }} {{ config('app.name') }}. כל הזכויות שמורות.</span>
            <a href="{{ route('privacy') }}" class="text-brand hover:underline">מדיניות פרטיות</a>
            <a href="{{ route('refund.policy') }}" class="text-brand hover:underline">מדיניות ביטולים</a>
        </div>

    </div>
</x-guest-layout>
