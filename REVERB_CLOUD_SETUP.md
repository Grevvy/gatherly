# Reverb Setup Guide

## Option 1: Laravel Cloud WebSockets (Recommended)

If you're deploying to Laravel Cloud, you can use their fully managed WebSocket infrastructure:

### Steps:
1. **Create WebSocket Cluster**: Go to your Organization's "Resources" page → "WebSockets" tab → "+ New WebSocket cluster"
2. **Select Configuration**: Choose region and max concurrent connections
3. **Attach to Environment**: In your application canvas, click "Add resource" → "WebSockets"
4. **Auto-Configuration**: Laravel Cloud automatically injects these environment variables:

```bash
REVERB_APP_ID=10001
REVERB_APP_KEY=********
REVERB_APP_SECRET=********
REVERB_HOST=ws-********-reverb.laravel.cloud
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

5. **Deploy**: Simply redeploy your application - no manual configuration needed!

### Benefits:
- ✅ Fully managed infrastructure
- ✅ Automatic SSL/TLS configuration
- ✅ Built-in scaling and high availability
- ✅ Integrated metrics and monitoring
- ✅ No DevOps expertise required

### Laravel Cloud WebSocket Features:
- **Metrics Dashboard**: View connection count and message rates
- **Easy Scaling**: Resize clusters or split connections across apps
- **Multiple Environments**: Attach same cluster to staging/production
- **Currently in Developer Preview** (as of Oct 2025)

---

## Option 2: Manual Cloud Server Setup

If you're using a different cloud provider, add these environment variables to your server:

### Core Application Settings
```bash
APP_NAME="Gatherly"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Enable Reverb broadcasting
BROADCAST_DRIVER=reverb
BROADCAST_CONNECTION=reverb
```

### Manual Reverb Configuration (Only for non-Laravel Cloud)
```bash
# Reverb Server Configuration  
REVERB_APP_KEY="your-secure-app-key"
REVERB_APP_SECRET="your-secure-app-secret"
REVERB_APP_ID="gatherly-prod"
REVERB_HOST="your-domain.com"
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Frontend Vite Configuration (for client-side connection)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Optional: Redis Scaling (for multiple server instances)
```bash
# Only enable if you need horizontal scaling on manual setup
REVERB_SCALING_ENABLED=false
REDIS_URL=redis://your-redis-server:6379
```

---

## Manual Server Setup Steps (Non-Laravel Cloud)

1. **SSL Certificate**: Ensure your domain has a valid SSL certificate for WSS connections

2. **Port Configuration**: 
   - Reverb will run on port 8080 internally
   - Expose it through your reverse proxy (nginx/apache) on port 443
   - Configure WebSocket upgrade headers

3. **Nginx Configuration Example**:
```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;
    
    # Regular Laravel app
    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    # WebSocket proxy for Reverb
    location /app/ {
        proxy_pass http://localhost:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

4. **Process Management**: Use a process manager like Supervisor to keep Reverb running:
```ini
[program:reverb]
command=php /path/to/your/app/artisan reverb:start
directory=/path/to/your/app
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

5. **Firewall**: Ensure ports 80, 443, and 8080 (internal) are properly configured

## Testing the Setup

After deployment, verify your WebSocket connection:

1. **Open Browser Console**: Go to your app and open Developer Tools (F12)
2. **Check Network Tab**: Look for WebSocket connections (filter by "WS")
3. **Test Real-time Messaging**: Send a message and verify it appears instantly for other users
4. **Console Logs**: Look for `[Broadcasting]` logs in the console

### Expected Console Output:
```
[Broadcasting] attempting to subscribe to channel.123
[Broadcasting] subscription_succeeded channel.123
New message received: {id: 456, body: "Hello!", user: {...}}
```

### Troubleshooting:
- **Connection Issues**: Check REVERB_HOST matches your domain
- **Auth Failures**: Verify CSRF token is being sent with auth requests
- **Mixed Content**: Ensure REVERB_SCHEME is 'https' in production
- **Laravel Cloud**: Check WebSocket metrics in Resources dashboard

## Security Notes

- Use strong, unique values for `REVERB_APP_KEY` and `REVERB_APP_SECRET`
- Keep the internal Reverb port (8080) behind your reverse proxy
- Consider IP whitelisting for internal services

## Troubleshooting

- Check WebSocket connection in browser dev tools (Network > WS)
- Verify SSL certificate chain for WSS connections
- Check server logs for Reverb connection errors
- Test with `wscat` or similar WebSocket testing tools