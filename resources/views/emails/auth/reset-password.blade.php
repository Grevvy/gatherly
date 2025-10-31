<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset your Gatherly password</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #111827;
            max-width: 600px;
            margin: 0 auto;
            padding: 24px;
            background: #f8fafc;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: #fff;
            padding: 24px;
            text-align: center;
        }

        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .content {
            padding: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 20px;
            color: #111827;
        }

        p {
            margin: 0 0 12px;
            color: #374151;
        }

        .button {
            display: inline-block;
            padding: 12px 20px;
            background: #1e40af;
            color: #fff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 12px 0;
        }

        .muted {
            color: #6b7280;
            font-size: 14px;
        }

        .footer {
            padding: 18px 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }

        .code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }

        .link {
            color: #1e40af;
            word-break: break-all;
        }
    </style>
    <!-- No Laravel branding; fully custom Gatherly template -->
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
</head>

<body>
    <div class="card">
        <div class="header">
            <div class="brand">Gatherly</div>
        </div>
        <div class="content">
            <h1>Password reset request</h1>
            <p>Hi {{ $user->name ?? 'there' }},</p>
            <p>We received a request to reset the password for your Gatherly account. Click the button below to choose a
                new password.</p>

            <p style="text-align:center;">
                <a class="button" href="{{ $resetUrl }}">Reset Password</a>
            </p>

            <p class="muted">This password reset link will expire in {{ $expires }} minutes.</p>

            <p>If you did not request a password reset, no action is required and you can safely ignore this email.</p>

            <p>Regards,<br />
                <strong>Gatherly</strong>
            </p>

        </div>
        <div class="footer">
            <p>This email was sent by <strong>Gatherly</strong><br>
                Building communities, hosting events, and collaborating in real time.
            </p>
        </div>
    </div>
</body>

</html>
