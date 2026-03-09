<x-mail::message>
# שלום {{ $user->name }},

ברוכים הבאים ל-Kalfa!

אנחנו שמחים שהצטרפת אלינו עם הארגון **{{ $organization->name }}**.
כעת תוכלו להתחיל לנהל את האירועים שלכם, להזמין אורחים, ולנהל את סידורי ההושבה בצורה חכמה.

@if($pdfUrl)
צירפנו עבורכם מסמך חשוב לעיון:
[הורד מסמך PDF]({{ $pdfUrl }})
@endif

<x-mail::button :url="route('dashboard')">
מעבר ללוח הבקרה
</x-mail::button>

תודה,<br>
צוות {{ config('app.name') }}
</x-mail::message>
