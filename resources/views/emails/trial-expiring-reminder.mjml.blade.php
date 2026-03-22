<mjml>
  <mj-head>
    <mj-title>תזכורת: תקופת הניסיון שלך עומדת להסתיים</mj-title>
    <mj-font name="Heebo" href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;700&display=swap" />
    <mj-html-attributes>
      <mj-selector path="html">
        <mj-html-attribute name="lang">he</mj-html-attribute>
        <mj-html-attribute name="dir">rtl</mj-html-attribute>
      </mj-selector>
      <mj-selector path="body">
        <mj-html-attribute name="dir">rtl</mj-html-attribute>
      </mj-selector>
      <mj-selector path="div[role='article']">
        <mj-html-attribute name="lang">he</mj-html-attribute>
        <mj-html-attribute name="dir">rtl</mj-html-attribute>
      </mj-selector>
    </mj-html-attributes>
    <mj-preview>
      תקופת הניסיון שלך במערכת עומדת להסתיים בעוד {{ $daysRemaining }} ימים
    </mj-preview>
    <mj-attributes>
      <mj-all font-family="Heebo, Arial, sans-serif" />
      <mj-text font-size="16px" color="#475569" line-height="1.6" align="right" />
    </mj-attributes>
    <mj-style>
      .email-button a { transition: opacity 0.2s; }
      .email-button a:hover { opacity: 0.9; }
    </mj-style>
  </mj-head>

  <mj-body background-color="#f8fafc">
    <!-- Logo Header -->
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

    <!-- Greeting & Intro -->
    <mj-section background-color="#ffffff" padding="40px 40px 20px 40px" direction="rtl">
      <mj-column>
        <mj-text font-size="24px" font-weight="bold" color="#1e293b" padding-bottom="20px" align="right">
          שלום <bdi dir="auto">{{ $ownerName ?? 'חבר יקר' }}</bdi>,
        </mj-text>
        <mj-text align="right">
          תקופת הניסיון שלך במערכת עומדת להסתיים בעוד
          <span style="background-color: #dcfce7; color: #166534; font-weight: bold; padding: 4px 12px; border-radius: 20px; font-size: 14px; white-space: nowrap;">
            <bdi dir="ltr">{{ $daysRemaining }}</bdi> ימים
          </span>
        </mj-text>
      </mj-column>
    </mj-section>

    <!-- Alert Card -->
    <mj-section background-color="#ffffff" padding="0 40px 30px 40px" direction="rtl">
      <mj-column background-color="#fef3c7" border-right="4px solid #f59e0b" border-radius="12px" padding="20px">
        <mj-text color="#78350f" align="right">
          <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold; color: #b45309;">
            ⚠️ תזכורת חשובה
          </h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.5;">
            תקופת הניסיון עומדת להסתיים בעוד <bdi dir="ltr">{{ $daysRemaining }}</bdi> ימים.
            לאחר הסיום, הגישה שלך למערכת תושהה.
          </p>
        </mj-text>
      </mj-column>
    </mj-section>

    <!-- Details Card -->
    <mj-section background-color="#ffffff" padding="0 40px 30px 40px" direction="rtl">
      <mj-column background-color="#f1f5f9" border-radius="12px" padding="24px">
        <mj-text font-size="18px" font-weight="600" color="#1e293b" padding-bottom="16px" align="right">
          📋 פרטי הניסיון שלך
        </mj-text>
        <mj-table line-height="1.5">
          <tr style="border-bottom: 1px solid #e2e8f0;">
            <td style="padding: 8px 0; font-size: 12px; color: #64748b; font-weight: 600; text-align: right;">ארגון</td>
            <td style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: left;"><bdi dir="auto">{{ $organizationName }}</bdi></td>
          </tr>
          <tr style="border-bottom: 1px solid #e2e8f0;">
            <td style="padding: 8px 0; font-size: 12px; color: #64748b; font-weight: 600; text-align: right;">תוכנית</td>
            <td style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: left;"><bdi dir="auto">{{ $planName }}</bdi></td>
          </tr>
          <tr>
            <td style="padding: 8px 0; font-size: 12px; color: #64748b; font-weight: 600; text-align: right;">תאריך סיום</td>
            <td style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: left;"><bdi dir="ltr">{{ $trialEndsAt->format('d/m/Y') }}</bdi></td>
          </tr>
        </mj-table>
      </mj-column>
    </mj-section>

    <!-- Info Section -->
    <mj-section background-color="#ffffff" padding="0 40px 30px 40px" direction="rtl">
      <mj-column>
        <mj-text font-size="18px" font-weight="600" color="#1e293b" padding-bottom="12px" align="right">
          מה קורה בסיום תקופת הניסיון?
        </mj-text>
        <mj-text align="right">
          בסיום תקופת הניסיון, הגישה שלך למערכת תושהה.
          כדי להמשיך להשתמש בכל התכונות, עליך לבחור תוכנית בתשלום.
        </mj-text>
      </mj-column>
    </mj-section>

    <!-- CTA Button -->
    <mj-section background-color="#ffffff" padding="0 40px 40px 40px">
      <mj-column>
        <mj-button
          href="{{ $selectPlanUrl }}"
          background-color="#3b82f6"
          color="#ffffff"
          font-size="16px"
          font-weight="bold"
          padding="16px 32px"
          border-radius="8px"
          align="center"
          css-class="email-button"
        >
          בחר תוכנית
        </mj-button>
      </mj-column>
    </mj-section>

    <!-- Footer -->
    <mj-section background-color="#f8fafc" padding="40px 20px">
      <mj-column>
        <mj-divider border-color="#e2e8f0" border-width="1px" padding-bottom="20px" />
        <mj-text font-size="12px" color="#64748b" align="center">
          © <bdi dir="ltr">{{ date('Y') }}</bdi> <bdi dir="ltr">{{ config('app.name') }}</bdi>. כל הזכויות שמורות.
        </mj-text>
        <mj-text font-size="11px" color="#94a3b8" align="center" padding-top="10px">
          נשלח אל דף בחירת תוכנית: <a href="{{ $selectPlanUrl }}" dir="ltr" style="color: #3b82f6; text-decoration: none; direction: ltr; unicode-bidi: isolate;"><bdi dir="ltr">{{ $selectPlanUrl }}</bdi></a>
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
