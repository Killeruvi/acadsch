<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Institutional Login Credentials</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 30px 15px; color: #1e293b;">

    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <!-- Header -->
        <tr>
            <td style="background-color: #059669; padding: 28px 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; text-transform: uppercase;">
                    {{ $school['name'] ?? 'PrimeArc Public School' }}
                </h1>
                <p style="color: #a7f3d0; margin: 6px 0 0; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                    Official Institutional Access Pass &bull; {{ $school['academic_session'] ?? '2026-2027' }}
                </p>
            </td>
        </tr>

        <!-- Body -->
        <tr>
            <td style="padding: 32px 30px;">
                <p style="font-size: 15px; margin: 0 0 16px; color: #334155;">
                    Dear <strong>{{ $user->name }}</strong>,
                </p>

                <p style="font-size: 14px; line-height: 1.6; margin: 0 0 24px; color: #475569;">
                    Your official institutional account has been initialized on the <strong>{{ $school['name'] ?? 'PrimeOS' }}</strong> cloud campus portal. Please find your secure login credentials below:
                </p>

                <!-- Credentials Box -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 24px;">
                    <tr>
                        <td style="padding: 20px 24px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; padding-bottom: 4px;">Role Profile</td>
                                    <td style="text-align: right; font-size: 11px; text-transform: uppercase; color: #059669; font-weight: 800; padding-bottom: 4px;">{{ strtoupper($user->role) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 12px; font-weight: 700; color: #334155; padding: 6px 0; border-top: 1px solid #e2e8f0;">Login Username / Email:</td>
                                    <td style="text-align: right; font-size: 13px; font-family: monospace; font-weight: 700; color: #0f172a; padding: 6px 0; border-top: 1px solid #e2e8f0;">{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 12px; font-weight: 700; color: #334155; padding: 6px 0; border-top: 1px solid #e2e8f0;">Temporary Password:</td>
                                    <td style="text-align: right; font-size: 14px; font-family: monospace; font-weight: 800; color: #dc2626; padding: 6px 0; border-top: 1px solid #e2e8f0; letter-spacing: 1px;">{{ $password }}</td>
                                </tr>
                                @if(!empty($user->admission_number) || !empty($user->student_id_number))
                                <tr>
                                    <td style="font-size: 12px; font-weight: 700; color: #334155; padding: 6px 0; border-top: 1px solid #e2e8f0;">Institutional ID / Adm No:</td>
                                    <td style="text-align: right; font-size: 13px; font-family: monospace; font-weight: 700; color: #334155; padding: 6px 0; border-top: 1px solid #e2e8f0;">{{ $user->admission_number ?? $user->student_id_number }}</td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Action Button -->
                <div style="text-align: center; margin-bottom: 28px;">
                    <a href="{{ $loginUrl }}" style="background-color: #059669; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 700; display: inline-block; box-shadow: 0 2px 4px rgba(5,150,105,0.2);">
                        Access School Portal &rarr;
                    </a>
                </div>

                <!-- Security Note -->
                <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 12px; color: #991b1b; line-height: 1.5;">
                        <strong>Security Advisory:</strong> For your privacy and data protection, please change this temporary password immediately after your first sign-in under <em>My Profile &rarr; Security Settings</em>.
                    </p>
                </div>

                <p style="font-size: 13px; color: #64748b; margin: 0; line-height: 1.5;">
                    If you did not request or expect this account, contact the institutional administration office using the contact details published by the school.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 30px; text-align: center;">
                <p style="margin: 0 0 6px; font-size: 11px; font-weight: 700; color: #475569;">
                    {{ $school['name'] ?? 'PrimeArc Public School' }} &bull; {{ $school['campus'] ?? 'Main Campus' }}
                </p>
                <p style="margin: 0; font-size: 10px; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px;">
                    ⚡ Powered by PrimeOS Institutional Cloud
                </p>
            </td>
        </tr>
    </table>

</body>
</html>
