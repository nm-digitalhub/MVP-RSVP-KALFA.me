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
          שלום <bdi dir="auto">{{ $user->name }}</bdi>,
        </mj-text>
        <mj-text font-size="20px" font-weight="bold" color="#3b82f6" padding-bottom="10px">
          ברוכים הבאים ל-<bdi dir="ltr">Kalfa</bdi>!
        </mj-text>
        <mj-text>
          אנחנו שמחים שהצטרפת אלינו עם הארגון <strong><bdi dir="auto">{{ $organization->name }}</bdi></strong>.
          כעת תוכלו להתחיל לנהל את האירועים שלכם, להזמין אורחים, ולנהל את סידורי ההושבה בצורה חכמה.
        </mj-text>
      </mj-column>
    </mj-section>

    @if($pdfUrl)
    <mj-section background-color="#f1f5f9" border-radius="12px" padding="20px" margin="0 40px 20px 40px">
      <mj-column>
        <mj-text font-size="14px" color="#1e293b">
          צירפנו עבורכם מסמך חשוב לעיון:
        </mj-text>
        <mj-button
          href="{{ $pdfUrl }}"
          background-color="#64748b"
          color="#ffffff"
          font-size="14px"
          padding="10px 20px"
          border-radius="6px"
        >
          הורד מסמך <span dir="ltr" style="unicode-bidi:isolate;">PDF</span>
        </mj-button>
      </mj-column>
    </mj-section>
    @endif

    <mj-section background-color="#ffffff" padding="0 40px 40px 40px">
      <mj-column>
        <mj-button
          href="{{ route('dashboard') }}"
          background-color="#3b82f6"
          color="#ffffff"
          font-size="16px"
          font-weight="bold"
          padding="16px 32px"
          border-radius="8px"
        >
          מעבר ללוח הבקרה
        </mj-button>
        <mj-text padding-top="20px">
          תודה,<br>
          צוות <bdi dir="ltr">{{ config('app.name') }}</bdi>
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
