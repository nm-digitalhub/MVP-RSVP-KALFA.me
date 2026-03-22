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

  <mj-body background-color="#f8fafc">
    <!-- Header -->
    <mj-section background-color="#3b82f6" padding="40px 20px">
      <mj-column>
        <mj-image
          src="{{ asset('images/Logo-Kalfa/Logo_Icon_(K).png') }}"
          alt="{{ config('app.name') }}"
          width="48px"
          height="48px"
          align="center"
        />
      </mj-column>
    </mj-section>

    <!-- Main Content Content -->
    <mj-section background-color="#ffffff" padding="40px">
      <mj-column>
        {{ $slot }}
      </mj-column>
    </mj-section>

    <!-- Footer -->
    <mj-section background-color="#f8fafc" padding="40px 20px">
      <mj-column>
        <mj-divider border-color="#e2e8f0" border-width="1px" padding-bottom="20px" />
        <mj-text font-size="12px" color="#64748b" align="center">
          © <bdi dir="ltr">{{ date('Y') }}</bdi> <bdi dir="ltr">{{ config('app.name') }}</bdi>. {{ __('All rights reserved.') }}
        </mj-text>
        @if($recipientEmail)
        <mj-text font-size="11px" color="#94a3b8" align="center" padding-top="10px">
            {{ __('This email was sent to') }} <bdi dir="ltr">{{ $recipientEmail }}</bdi>. {{ __('If you did not request this email, you can safely ignore it.') }}
        </mj-text>
        @endif
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
