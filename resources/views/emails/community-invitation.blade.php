<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Invitation - {{ $community->name }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #f9fafb;
        }

        .container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px;
            margin: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f9ff;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #1d4ed8;
            margin-bottom: 10px;
        }

        .invitation-banner {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 25px;
        }

        .invitation-banner h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
        }

        .invitation-banner p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }

        .community-info {
            background-color: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }

        .community-name {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .community-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-accept {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-decline {
            background-color: #f3f4f6;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }

        .btn-decline:hover {
            background-color: #e5e7eb;
            color: #374151;
        }

        .info-section {
            margin: 25px 0;
        }

        .info-section h3 {
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .info-section ul {
            margin: 0;
            padding-left: 20px;
        }

        .info-section li {
            margin-bottom: 5px;
            color: #64748b;
        }

        .note {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
            color: #92400e;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 14px;
        }

        .footer strong {
            color: #1d4ed8;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">🌟 Gatherly</div>
        </div>

        <div class="invitation-banner">
            <h1>🎉 You're Invited!</h1>
            <p>Someone wants you to join their community</p>
        </div>

        <p>Hi {{ $user->name }},</p>

        <p>Great news! You've been invited to join <strong>{{ $community->name }}</strong>, an exclusive 
        invite-only community on Gatherly.</p>

        <div class="community-info">
            <div class="community-name">{{ $community->name }}</div>
            @if($community->description)
                <div class="community-description">{{ $community->description }}</div>
            @endif
        </div>

        <div class="info-section">
            <h3>What happens when you accept?</h3>
            <ul>
                <li>🤝 Connect with like-minded community members</li>
                <li>💬 Participate in discussions and conversations</li>
                <li>📅 Join community events and activities</li>
                <li>📸 Share photos and memorable moments</li>
                <li>🔔 Get notified about important community updates</li>
            </ul>
        </div>

        <div class="action-buttons">
            <a href="{{ $acceptUrl }}" class="btn btn-accept">✅ Accept Invitation</a>
            <a href="{{ $declineUrl }}" class="btn btn-decline">❌ Decline</a>
        </div>

        <div class="note">
            <strong>💡 Note:</strong> This invitation is personal to you. The community moderators 
            specifically thought you'd be a great fit for their community.
        </div>

        <p><strong>Why Gatherly?</strong></p>
        <ul>
            <li>🏠 Build meaningful connections in private communities</li>
            <li>📱 Easy-to-use platform for all your community needs</li>
            <li>🎯 Focused discussions without the noise</li>
        </ul>

        <div class="footer">
            <p>This email was sent by <strong>Gatherly</strong><br>
                Building communities, hosting events, and collaborating in real time.
            </p>
            <p style="margin-top: 10px; font-size: 12px;">
                If you didn't expect this invitation, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>

</html>