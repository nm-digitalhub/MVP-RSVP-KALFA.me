@component('emails.layouts.email', ['subject' => 'Reminder: Your trial is ending soon'])

    <!-- Greeting -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td>
                <h1 class="email-text" style="margin: 0; font-size: 24px; font-weight: 700; color: #1e293b;">
                    Hello {{ $ownerName ?? 'there' }},
                </h1>
                <p class="email-text" style="margin: 8px 0 0 0; font-size: 16px; color: #475569; line-height: 1.6;">
                    Your trial period in the system is ending in
                    <span style="display: inline-block; background: #dcfce7; color: #166534; font-weight: 700; padding: 4px 12px; border-radius: 20px; font-size: 14px; margin: 0 4px;">
                        {{ $daysRemaining }} day(s)
                    </span>
                </p>
            </td>
        </tr>
    </table>

    <!-- Alert Card -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; border-radius: 12px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding-right: 12px;">
                            <span style="font-size: 24px;">⚠️</span>
                        </td>
                        <td>
                            <h3 class="email-text" style="margin: 0 0 8px 0; font-size: 16px; font-weight: 700; color: #b45309;">
                                Important Reminder
                            </h3>
                            <p class="email-text" style="margin: 0; font-size: 14px; color: #78350f; line-height: 1.5;">
                                Your trial period is ending in {{ $daysRemaining }} day(s). After the end, your access to the system will be suspended.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Details Card -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background: #f1f5f9; padding: 24px; border-radius: 12px;">
                <h3 class="email-text" style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1e293b;">
                    📋 Your Trial Details
                </h3>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                            <span class="email-text" style="font-size: 12px; color: #64748b; font-weight: 600;">Organization</span>
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right;">
                            <span class="email-text" style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ $organizationName }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                            <span class="email-text" style="font-size: 12px; color: #64748b; font-weight: 600;">Plan</span>
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right;">
                            <span class="email-text" style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ $planName }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;">
                            <span class="email-text" style="font-size: 12px; color: #64748b; font-weight: 600;">End Date</span>
                        </td>
                        <td style="padding: 8px 0; text-align: right;">
                            <span class="email-text" style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ $trialEndsAt->format('M d, Y') }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Info Section -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 32px;">
        <tr>
            <td>
                <h3 class="email-text" style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600; color: #1e293b;">
                    What happens when your trial ends?
                </h3>
                <p class="email-text" style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6;">
                    When your trial period ends, your access to the system will be suspended. To continue using all features, you need to choose a paid plan.
                </p>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 32px;">
        <tr>
            <td style="text-align: center;">
                <a href="{{ $selectPlanUrl }}" style="display: inline-block; background: #3b82f6; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 14px;" class="email-button">
                    Choose a Plan
                </a>
            </td>
        </tr>
    </table>

    <!-- Quick Links -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="padding-top: 24px; border-top: 1px solid #e2e8f0;">
                <p class="email-text" style="margin: 0 0 8px 0; font-size: 11px; color: #94a3b8; font-weight: 600;">
                    Quick Links:
                </p>
                <p class="email-text" style="margin: 0; font-size: 11px; color: #64748b;">
                    Plan selection page:
                    <a href="{{ $selectPlanUrl }}" style="color: #3b82f6; text-decoration: none;">{{ $selectPlanUrl }}</a>
                </p>
                <p class="email-text" style="margin: 16px 0 0 0; font-size: 11px; color: #64748b;">
                    If you have any questions, please don't hesitate to reach out to us.
                </p>
            </td>
        </tr>
    </table>

@endcomponent
