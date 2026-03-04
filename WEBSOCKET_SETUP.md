# WebSocket Integration for Real-Time Payment Updates

## Quick Setup Guide

### 1. Install Laravel WebSockets Package

```bash
composer require beyondcode/laravel-websockets
composer require pusher/pusher-php-server
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

### 2. Update .env File

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

QUEUE_CONNECTION=database  # or redis
```

### 3. Start Required Services

```bash
# Start WebSocket server
php artisan websockets:serve

# Start queue worker (in another terminal)
php artisan queue:work

# Start Laravel development server (if not already running)
php artisan serve
```

### 4. Test the Implementation

#### Backend Test (Postman)

1. **Checkout**: POST to `/api/v1/checkout`
    - Response will include `websocket` object with channel info
2. **Simulate Callback**: POST to `/api/v1/payments/callback/selcom`
    - Use valid payment reference from step 1
    - Watch logs to see event broadcast

#### Frontend Test

See `docs/WEBSOCKET_PAYMENT_STATUS.md` for complete client implementation examples.

Quick JavaScript test:

```javascript
import Pusher from "pusher-js";

const pusher = new Pusher("local", {
    cluster: "mt1",
    wsHost: "127.0.0.1",
    wsPort: 6001,
    forceTLS: false,
    authEndpoint: "http://localhost:8000/api/broadcasting/auth",
    auth: {
        headers: {
            Authorization: "Bearer YOUR_TOKEN",
        },
    },
});

const channel = pusher.subscribe("private-payment.YOUR_PAYMENT_REFERENCE");

channel.bind("payment.status.updated", (data) => {
    console.log("Payment update received:", data);
});
```

## Files Added/Modified

### New Files

- ✅ `config/broadcasting.php` - Broadcasting configuration
- ✅ `app/Providers/BroadcastServiceProvider.php` - Broadcasting service provider
- ✅ `routes/channels.php` - Channel authorization
- ✅ `app/Events/PaymentStatusUpdated.php` - Payment status event
- ✅ `docs/WEBSOCKET_PAYMENT_STATUS.md` - Complete documentation

### Modified Files

- ✅ `bootstrap/providers.php` - Registered BroadcastServiceProvider
- ✅ `app/Http/Controllers/PaymentController.php` - Added event broadcasting
- ✅ `.env.example` - Added broadcasting configuration

## How It Works

1. **Checkout**: User initiates payment → receives payment reference
2. **Subscribe**: Client connects to WebSocket and subscribes to `private-payment.{reference}`
3. **Callback**: Selcom sends payment status → API updates payment
4. **Broadcast**: `PaymentStatusUpdated` event is broadcast to subscribed clients
5. **Receive**: Client receives real-time update and can show success/failure

## Production Deployment

### Using Supervisor for WebSocket Server

Create `/etc/supervisor/conf.d/websockets.conf`:

```ini
[program:websockets]
command=php /var/www/your-app/artisan websockets:serve
directory=/var/www/your-app
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/websockets.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start websockets
```

### Nginx Configuration

Add to your nginx site config:

```nginx
location /app {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}
```

## Troubleshooting

### WebSocket Server Won't Start

```bash
# Check if port 6001 is already in use
lsof -i :6001

# Check logs
tail -f storage/logs/laravel.log
```

### Events Not Broadcasting

```bash
# Make sure queue worker is running
php artisan queue:work

# Check queue jobs
php artisan queue:failed
```

### Client Can't Connect

- Verify Bearer token is valid
- Check CORS settings in `config/cors.php`
- Ensure broadcasting auth endpoint is accessible
- Check browser console for errors

## Alternative: Using Pusher Cloud

If you prefer not to self-host WebSockets:

1. Sign up at [pusher.com](https://pusher.com)
2. Create a new app
3. Update `.env`:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

4. No need to run `websockets:serve`

## Resources

- [Full Documentation](docs/WEBSOCKET_PAYMENT_STATUS.md)
- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel WebSockets](https://beyondco.de/docs/laravel-websockets)
- [Pusher JS Client](https://github.com/pusher/pusher-js)
