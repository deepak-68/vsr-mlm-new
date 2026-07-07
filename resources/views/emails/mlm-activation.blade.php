<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Your MLM Account</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background: #f5f7fa; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px 20px; }
        .content p { margin: 15px 0; }
        .btn { display: inline-block; background: #667eea; color: #fff !important; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
        .expiry { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 15px; margin: 20px 0; border-radius: 4px; font-size: 14px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e9ecef; }
        .fallback-url { background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to VSRMLM!</h1>
        </div>

        <div class="content">
            <p>Hi <strong>{{ $firstName }}</strong>,</p>

            <p>Your MLM account has been successfully created with the following details:</p>

            <table style="width:100%; border-collapse:collapse; margin:20px 0;">
                <tr>
                    <td style="padding:8px 0; font-weight:600;">Username:</td>
                    <td style="padding:8px 0;">{{ $userName }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; font-weight:600;">Email:</td>
                    <td style="padding:8px 0;">{{ $user->email }}</td>
                </tr>
            </table>

            <p>To activate your account and start building your binary tree, please click the button below:</p>

            <p style="text-align:center;">
                <a href="{{ $activationUrl }}" class="btn" rel="noopener noreferrer">Activate My Account</a>
            </p>

            <div class="expiry">
                <strong>Security Notice:</strong> This activation link will expire in {{ $expiryHours }} hours.
            </div>

            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p class="fallback-url">{{ $activationUrl }}</p>
        </div>

        <div class="footer">
            <p>If you didn't create this account, please ignore this email.</p>
            <p>&copy; {{ date('Y') }} <strong>{{ config('app.name', 'VSRMLM') }}</strong>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
