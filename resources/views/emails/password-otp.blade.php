<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $schoolName }} Password Reset Code</title>
</head>
<body style="margin:0;padding:0;background-color:#0f172a;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#020617;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:480px;background:#020617;border-radius:16px;border:1px solid #1f2937;">
                <tr>
                    <td style="padding:24px 24px 8px 24px;" align="center">
                        <div style="height:42px;width:42px;border-radius:999px;background:linear-gradient(135deg,#0ea5e9,#10b981);display:flex;align-items:center;justify-content:center;color:#020617;font-weight:700;font-size:18px;">
                            S
                        </div>
                        <h1 style="margin-top:16px;margin-bottom:4px;font-size:20px;color:#e5e7eb;">{{ $schoolName }}</h1>
                        <p style="margin:0;font-size:12px;letter-spacing:0.16em;text-transform:uppercase;color:#6b7280;">
                            Password Reset
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px 8px 24px;">
                        <p style="margin:0 0 12px 0;font-size:14px;color:#d1d5db;">
                            You recently requested to reset your account password.
                        </p>
                        <p style="margin:0 0 16px 0;font-size:14px;color:#9ca3af;">
                            Use the verification code below to continue. This code is valid for a limited time and should not be shared with anyone.
                        </p>
                        <div style="margin:16px 0;padding:12px 16px;border-radius:999px;background:rgba(8,47,73,0.9);border:1px solid rgba(56,189,248,0.5);color:#e5e7eb;font-size:18px;letter-spacing:0.4em;text-align:center;font-weight:600;">
                            {{ $code }}
                        </div>
                        <p style="margin:0 0 12px 0;font-size:12px;color:#6b7280;">
                            If you did not request this, you can safely ignore this email and your password will remain unchanged.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 24px 20px 24px;" align="center">
                        <p style="margin:0;font-size:11px;color:#4b5563;">
                            This message was generated automatically by {{ $schoolName }}. Please do not reply to this email.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

