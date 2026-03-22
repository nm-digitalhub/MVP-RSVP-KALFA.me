@component('emails.layouts.email', ['subject' => 'תזכורת: תקופת הניסיון שלך עומדת להסתיים'])

    <!-- Greeting -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="text-align: {{ app()->getLocale() === 'he' ? 'right' : 'left' }};">
                <h1 class="email-text" style="margin: 0; font-size: 24px; font-weight: 700; color: #1e293b;">
                    שלום {{ $ownerName ?? 'חבר יקר'}},
                </h1>
                <p class="email-text" style="margin: 8px 0 0 0; font-size: 16px; color: #475569; line-height: 1.6;">
                    תקופת הניסיון שלך במערכת עומדת להסתיים בעוד
                    <span style="display: inline-block; background: #dcfce7; color: #166534; font-weight: 700; padding: 4px 12px; border-radius: 20px; font-size: 14px; margin: 0 4px;">
                        {{ $daysRemaining }} ימים
                    </span>
                </p>
            </td>
        </tr>
    </table>

    <!-- Alert Card -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
        <tr>
            <td style="background: #fef3c7; border-{{ app()->getLocale() === 'he' ? 'right' : 'left' }}: 4px solid #f59e0b; padding: 20px; border-radius: 12px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding-{{ app()->getLocale() === 'he' ? 'left' : 'right' }}: 12px;">
                            <span style="font-size: 24px;">⚠️</span>
                        </td>
                        <td>
                            <h3 class="email-text" style="margin: 0 0 8px 0; font-size: 16px; font-weight: 700; color: #b45309;">
                                תזכורת חשובה
                            </h3>
                            <p class="email-text" style="margin: 0; font-size: 14px; color: #78350f; line-height: 1.5;">
                                תקופת הניסיון עומדת להסתיים בעוד {{ $daysRemaining }} ימים. לאחר הסיום, הגישה שלך למערכת תושהה.
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
                    📋 פרטי הניסיון שלך
                </h3>
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                            <span class="email-text" style="font-size: 12px; color: #64748b; font-weight: 600;">ארגון</span>
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: {{ app()->getLocale() === 'he' ? 'left' : 'right' }};">
                            <span class="email-text" style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ $organizationName }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                            <span class="email-text" style="font-size: 12px; color: #64748b; font-weight: 600;">תוכנית</span>
                        </td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: {{ app()->getLocale() === 'he' ? 'left' : 'right' }};">
                            <span class="email-text" style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ $planName }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0;">
                            <span class="email-text" style="font-size: 12px; color: #64748b; font-weight: 600;">תאריך סיום</span>
                        </td>
                        <td style="padding: 8px 0; text-align: {{ app()->getLocale() === 'he' ? 'left' : 'right' }};">
                            <span class="email-text" style="font-size: 14px; color: #1e293b; font-weight: 500;">{{ $trialEndsAt->format('d/m/Y') }}</span>
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
                    מה קורה בסיום תקופת הניסיון?
                </h3>
                <p class="email-text" style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6;">
                    בסיום תקופת הניסיון, הגישה שלך למערכת תושהה. כדי להמשיך להשתמש בכל התכונות, עליך לבחור תוכנית בתשלום.
                </p>
            </td>
        </tr>
    </table>

    <!-- CTA Button -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 32px;">
        <tr>
            <td style="text-align: center;">
                <a href="{{ $selectPlanUrl }}" style="display: inline-block; background: #3b82f6; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 14px;" class="email-button">
                    בחר תוכנית
                </a>
            </td>
        </tr>
    </table>

    <!-- Quick Links -->
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td style="padding-top: 24px; border-top: 1px solid #e2e8f0;">
                <p class="email-text" style="margin: 0 0 8px 0; font-size: 11px; color: #94a3b8; font-weight: 600;">
                    קישורים מהירים:
                </p>
                <p class="email-text" style="margin: 0; font-size: 11px; color: #64748b;">
                    דף בחירת תוכנית:
                    <a href="{{ $selectPlanUrl }}" style="color: #3b82f6; text-decoration: none;">{{ $selectPlanUrl }}</a>
                </p>
                <p class="email-text" style="margin: 16px 0 0 0; font-size: 11px; color: #64748b;">
                    אם יש לך שאלות, אל תהסס לפנות אלינו.
                </p>
            </td>
        </tr>
    </table>

@endcomponent
