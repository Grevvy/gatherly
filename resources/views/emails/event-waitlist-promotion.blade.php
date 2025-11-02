<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're In! Waitlist Promotion</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }

        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: #fff;
            padding: 24px;
            text-align: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            margin: -40px -40px 24px -40px;
            /* extend over container padding */
        }

        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .title {
            color: #1f2937;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .event-details {
            background-color: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
        }

        .event-title {
            font-size: 20px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .detail-row {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
            margin-right: 10px;
            min-width: 80px;
        }

        .detail-value {
            color: #6b7280;
        }

        .button {
            display: inline-block;
            background-color: #10b981;
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 14px;
        }

        .promotion-message {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .celebration {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="brand">Gatherly</div>
        </div>

        <div class="promotion-message">
            <div class="celebration">🎉</div>
            <h2 style="margin: 0; font-size: 20px;">Great News! You're Off the Waitlist!</h2>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">A spot just opened up and you're now confirmed to attend!</p>
        </div>

        <h1 class="title">Hello {{ $user->name }}!</h1>

        <p>Fantastic news! You've been moved from the waitlist to the confirmed attendees list for:</p>

        <div class="event-details">
            <h2 class="event-title">{{ $event->title }}</h2>

            @if ($event->description)
                <p style="color: #6b7280; margin-bottom: 15px;">{{ $event->description }}</p>
            @endif

            <div class="detail-row">
                <span class="detail-label">📅 Date:</span>
                <span class="detail-value">{{ $event->starts_at?->format('l, F j, Y') }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">🕐 Time:</span>
                <span class="detail-value">
                    {{ $event->starts_at?->format('g:i A') }}
                    @if ($event->ends_at)
                        - {{ $event->ends_at?->format('g:i A') }}
                    @endif
                </span>
            </div>

            @if ($event->location)
                <div class="detail-row">
                    <span class="detail-label">📍 Location:</span>
                    <span class="detail-value">{{ $event->location }}</span>
                </div>
            @endif

            @if ($event->community)
                <div class="detail-row">
                    <span class="detail-label">👥 Community:</span>
                    <span class="detail-value">{{ $event->community->name }}</span>
                </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">🎯 Host:</span>
                <span class="detail-value">{{ $event->owner->name ?? 'Community Event' }}</span>
            </div>
        </div>

        <div
            style="background-color: #dcfce7; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #10b981;">
            <p style="margin: 0; color: #166534;"><strong>🎯 Your Spot is Secured!</strong></p>
            <p style="margin: 5px 0 0 0; color: #16a34a;">You no longer need to worry about being on the waitlist. Your
                attendance is now confirmed!</p>
        </div>

        <p><strong>What's Next?</strong></p>
        <ul>
            <li>💬 Join the community discussion about this event</li>
            <li>🔔 You'll receive a reminder email closer to the event date</li>
            <li>🤝 Get ready to meet awesome people at the event!</li>
        </ul>

        <div style="background-color: #f0f9ff; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0; color: #0369a1;"><strong>⏰ Time Sensitive:</strong> If your plans change, please update
                your RSVP as soon as possible so we can offer the spot to someone else on the waitlist.</p>
        </div>

        <div class="footer">
            <p>This email was sent by <strong>Gatherly</strong><br>
                Building communities, hosting events, and collaborating in real time.
            </p>
        </div>
    </div>
</body>

</html>
