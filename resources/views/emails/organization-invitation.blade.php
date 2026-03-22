<x-mail::message>
# שלום!

הוזמנת להצטרף לארגון **{{ $invitation->organization->name }}** בתפקיד **{{ $invitation->role->value }}**.

לחץ על הכפתור למטה כדי לקבל את ההזמנה ולהצטרף לצוות.

<x-mail::button :url="$acceptUrl">
קבל הזמנה
</x-mail::button>

ההזמנה הזו תפוג בתאריך {{ $invitation->expires_at->format('Y-m-d H:i') }}.

אם לא ציפית להזמנה הזו, ניתן להתעלם ממנה בבטחה.

תודה,<br>
{{ config('app.name') }}
</x-mail::message>
