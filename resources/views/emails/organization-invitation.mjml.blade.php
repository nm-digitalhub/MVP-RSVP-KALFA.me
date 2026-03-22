<mjml>
  <mj-head>
    <mj-title>{{ $subject ?? 'Email' }}</mj-title>
    <mj-font name="Heebo" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;700&display=swap" />
    <mj-html-attributes>
      <mj-selector path="html">
        <mj-html-attribute name="lang">{{ $mailLanguage }}</mj-html-attribute>
        <mj-html-attribute name="dir">{{ $mailDirection }}</mj-html-attribute>
      </mj-selector>
      <mj-selector path="body">
        <mj-html-attribute name="dir">{{ $mailDirection }}</mj-html-attribute>
      </mj-selector>
      <mj-selector path="div[role='article']">
        <mj-html-attribute name="lang">{{ $mailLanguage }}</mj-html-attribute>
        <mj-html-attribute name="dir">{{ $mailDirection }}</mj-html-attribute>
      </mj-selector>
    </mj-html-attributes>
    <mj-attributes>
      <mj-all font-family="Heebo, Arial, sans-serif" />
      <mj-text font-size="16px" color="#475569" line-height="1.6" align="{{ $mailTextAlign }}" />
    </mj-attributes>
    <mj-style>
      .email-button a { transition: opacity 0.2s; }
      .email-button a:hover { opacity: 0.9; }
    </mj-style>
  </mj-head>
  <mj-body>
    <mj-section background-color="#ffffff" padding="0 40px 20px 40px">
      <mj-column>
        <mj-text font-size="24px" font-weight="bold" color="#1e293b" padding-bottom="20px">
          שלום!
        </mj-text>
        <mj-text>
          הוזמנת להצטרף לארגון <strong><bdi dir="auto">{{ $invitation->organization->name }}</bdi></strong> בתפקיד <strong><bdi dir="auto">{{ $invitation->role->value }}</bdi></strong>.
        </mj-text>
        <mj-text padding-top="10px">
          לחץ על הכפתור למטה כדי לקבל את ההזמנה ולהצטרף לצוות.
        </mj-text>
      </mj-column>
    </mj-section>

    <mj-section background-color="#ffffff" padding="0 40px 30px 40px">
      <mj-column>
        <mj-button
          href="{{ $acceptUrl }}"
          background-color="#3b82f6"
          color="#ffffff"
          font-size="16px"
          font-weight="bold"
          padding="16px 32px"
          border-radius="8px"
        >
          קבל הזמנה
        </mj-button>
      </mj-column>
    </mj-section>

    <mj-section background-color="#ffffff" padding="0 40px 40px 40px">
      <mj-column>
        <mj-text font-size="14px" color="#64748b">
          ההזמנה הזו תפוג בתאריך <bdi dir="ltr">{{ $invitation->expires_at->format('Y-m-d H:i') }}</bdi>.
        </mj-text>
        <mj-text font-size="14px" color="#64748b" padding-top="10px">
          אם לא ציפית להזמנה הזו, ניתן להתעלם ממנה בבטחה.
        </mj-text>
        <mj-text padding-top="20px">
          תודה,<br>
          <bdi dir="ltr">{{ config('app.name') }}</bdi>
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
