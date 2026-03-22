<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'he' ? 'rtl' : 'ltr' }}" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject ?? 'Email' }}</title>

    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->

    <style>
        /* Reset styles */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; }

        /* Responsive styles */
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; }
            .email-padding { padding: 20px !important; }
            .email-button { width: 100% !important; }
            .email-logo { width: 40px !important; height: 40px !important; }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-content { background: #1e293b !important; }
            .email-text { color: #e2e8f0 !important; }
            .email-card { background: #334155 !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100%; background: #f8fafc;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: #f8fafc;">
        <tr>
            <td style="padding: 40px 20px;">
                <!-- Email Container -->
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);" class="email-container">

                    <!-- Header -->
                    <tr>
                        <td style="padding: 32px; text-align: center; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 16px 16px 0 0;">
                            <img src="{{ asset('images/Logo-Kalfa/Logo_Icon_(K).png') }}" alt="{{ config('app.name') }}" width="48" height="48" style="display: block; margin: 0 auto;" class="email-logo">
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="email-content email-padding" style="padding: 32px; background: #ffffff; border-radius: 0 0 16px 16px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 32px; text-align: center; background: #f8fafc; border-radius: 0 0 16px 16px;">
                            <p style="margin: 0; font-size: 12px; color: #64748b;">
                                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 11px; color: #94a3b8;">
                                {{ __('This email was sent to :email. If you did not request this email, you can safely ignore it.', ['email' => $recipientEmail ?? '']) }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
