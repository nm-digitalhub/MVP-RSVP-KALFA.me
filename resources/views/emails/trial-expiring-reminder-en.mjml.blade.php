<mjml>
  <mj-head>
    <mj-title>Reminder: Your trial is ending soon</mj-title>
    <mj-font name="Inter" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" />
    <mj-html-attributes>
      <mj-selector path="html">
        <mj-html-attribute name="lang">en</mj-html-attribute>
        <mj-html-attribute name="dir">ltr</mj-html-attribute>
      </mj-selector>
      <mj-selector path="body">
        <mj-html-attribute name="dir">ltr</mj-html-attribute>
      </mj-selector>
      <mj-selector path="div[role='article']">
        <mj-html-attribute name="lang">en</mj-html-attribute>
        <mj-html-attribute name="dir">ltr</mj-html-attribute>
      </mj-selector>
    </mj-html-attributes>
    <mj-preview>
      Your trial period in the system is ending in {{ $daysRemaining }} day(s)
    </mj-preview>
    <mj-attributes>
      <mj-all font-family="Inter, Arial, sans-serif" />
      <mj-text font-size="16px" color="#475569" line-height="1.6" align="left" />
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
    <mj-section background-color="#ffffff" padding="40px 40px 20px 40px">
      <mj-column>
        <mj-text font-size="24px" font-weight="bold" color="#1e293b" padding-bottom="20px">
          Hello <bdi dir="auto">{{ $ownerName ?? 'there' }}</bdi>,
        </mj-text>
        <mj-text>
          Your trial period in the system is ending in
          <span style="background-color: #dcfce7; color: #166534; font-weight: bold; padding: 4px 12px; border-radius: 20px; font-size: 14px; white-space: nowrap;">
            {{ $daysRemaining }} day(s)
          </span>
        </mj-text>
      </mj-column>
    </mj-section>

    <!-- Alert Card -->
    <mj-section background-color="#ffffff" padding="0 40px 30px 40px">
      <mj-column background-color="#fef3c7" border-left="4px solid #f59e0b" border-radius="12px" padding="20px">
        <mj-text color="#78350f">
          <h3 style="margin: 0 0 8px 0; font-size: 16px; font-weight: bold; color: #b45309;">
            ⚠️ Important Reminder
          </h3>
          <p style="margin: 0; font-size: 14px; line-height: 1.5;">
            Your trial period is ending in {{ $daysRemaining }} day(s).
            After the end, your access to the system will be suspended.
          </p>
        </mj-text>
      </mj-column>
    </mj-section>

    <!-- Details Card -->
    <mj-section background-color="#ffffff" padding="0 40px 30px 40px">
      <mj-column background-color="#f1f5f9" border-radius="12px" padding="24px">
        <mj-text font-size="18px" font-weight="600" color="#1e293b" padding-bottom="16px">
          📋 Your Trial Details
        </mj-text>
        <mj-table line-height="1.5">
          <tr style="border-bottom: 1px solid #e2e8f0;">
            <td style="padding: 8px 0; font-size: 12px; color: #64748b; font-weight: 600;">Organization</td>
            <td style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: right;"><bdi dir="auto">{{ $organizationName }}</bdi></td>
          </tr>
          <tr style="border-bottom: 1px solid #e2e8f0;">
            <td style="padding: 8px 0; font-size: 12px; color: #64748b; font-weight: 600;">Plan</td>
            <td style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: right;"><bdi dir="auto">{{ $planName }}</bdi></td>
          </tr>
          <tr>
            <td style="padding: 8px 0; font-size: 12px; color: #64748b; font-weight: 600;">End Date</td>
            <td style="padding: 8px 0; font-size: 14px; color: #1e293b; text-align: right;"><bdi dir="ltr">{{ $trialEndsAt->format('M d, Y') }}</bdi></td>
          </tr>
        </mj-table>
      </mj-column>
    </mj-section>

    <!-- Info Section -->
    <mj-section background-color="#ffffff" padding="0 40px 30px 40px">
      <mj-column>
        <mj-text font-size="18px" font-weight="600" color="#1e293b" padding-bottom="12px">
          What happens when your trial ends?
        </mj-text>
        <mj-text>
          When your trial period ends, your access to the system will be suspended.
          To continue using all features, you need to choose a paid plan.
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
          Choose a Plan
        </mj-button>
      </mj-column>
    </mj-section>

    <!-- Footer -->
    <mj-section background-color="#f8fafc" padding="40px 20px">
      <mj-column>
        <mj-divider border-color="#e2e8f0" border-width="1px" padding-bottom="20px" />
        <mj-text font-size="12px" color="#64748b" align="center">
          © <bdi dir="ltr">{{ date('Y') }}</bdi> <bdi dir="ltr">{{ config('app.name') }}</bdi>. All rights reserved.
        </mj-text>
        <mj-text font-size="11px" color="#94a3b8" align="center" padding-top="10px">
          Sent to plan selection page: <a href="{{ $selectPlanUrl }}" dir="ltr" style="color: #3b82f6; text-decoration: none; direction: ltr; unicode-bidi: isolate;"><bdi dir="ltr">{{ $selectPlanUrl }}</bdi></a>
        </mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
