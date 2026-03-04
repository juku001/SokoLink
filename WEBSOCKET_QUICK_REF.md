# 🚀 WebSocket Quick Reference Card

## Setup (One-Time)

```bash
# Run the automated setup script
./setup-websockets.sh

# OR manual setup:
composer require beyondcode/laravel-websockets pusher/pusher-php-server
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider"
php artisan migrate
```

## Start Services (Every Time)

```bash
# Terminal 1: WebSocket Server
php artisan websockets:serve

# Terminal 2: Queue Worker
php artisan queue:work

# Terminal 3: Laravel Server
php artisan serve
```

## Test WebSocket

**Browser Test Tool**: http://localhost:8000/websocket-test.html

**Steps**:

1. Login to get Bearer token
2. Make checkout request → get payment reference
3. Enter token + reference in test tool
4. Click "Connect"
5. Send Selcom callback via Postman
6. Watch real-time update arrive! 🎉

## Client Implementation

### JavaScript

```javascript
import Pusher from "pusher-js";

const pusher = new Pusher("local", {
    cluster: "mt1",
    wsHost: "localhost",
    wsPort: 6001,
    forceTLS: false,
    authEndpoint: "http://localhost:8000/api/broadcasting/auth",
    auth: { headers: { Authorization: `Bearer ${token}` } },
});

const channel = pusher.subscribe(`private-payment.${reference}`);

channel.bind("payment.status.updated", (data) => {
    if (data.new_status === "successful") {
        alert("Payment successful! " + data.message);
    } else if (data.new_status === "failed") {
        alert("Payment failed: " + data.message);
    }
});
```

### React Hook

```typescript
useEffect(() => {
    if (!paymentRef || !token) return;

    const pusher = new Pusher(process.env.REACT_APP_PUSHER_KEY!, {
        cluster: "mt1",
        wsHost: process.env.REACT_APP_WS_HOST!,
        wsPort: parseInt(process.env.REACT_APP_WS_PORT || "6001"),
        authEndpoint: `${API_URL}/api/broadcasting/auth`,
        auth: { headers: { Authorization: `Bearer ${token}` } },
    });

    const channel = pusher.subscribe(`private-payment.${paymentRef}`);
    channel.bind("payment.status.updated", handleUpdate);

    return () => {
        channel.unbind_all();
        pusher.disconnect();
    };
}, [paymentRef, token]);
```

### Flutter

```dart
final pusher = PusherClient('local', PusherOptions(
  host: 'your-domain.com',
  wsPort: 6001,
  auth: PusherAuth(
    'https://your-api.com/api/broadcasting/auth',
    headers: {'Authorization': 'Bearer $token'},
  ),
));

pusher.connect();
final channel = pusher.subscribe('private-payment.$reference');
channel.bind('payment.status.updated', (event) {
  final data = jsonDecode(event.data);
  if (data['new_status'] == 'successful') {
    showSuccessDialog(data['message']);
  }
});
```

## API Response Structure

### Checkout Response (includes WebSocket info)

```json
{
    "status": true,
    "data": {
        "payment_id": 101,
        "reference": "a1b2c3d4-e5f6...",
        "message": "Payment initiated",
        "websocket": {
            "channel": "private-payment.a1b2c3d4-e5f6...",
            "event": "payment.status.updated",
            "auth_endpoint": "https://api.sokolink.co.tz/api/broadcasting/auth"
        }
    }
}
```

### WebSocket Event Data

```json
{
    "payment_id": 101,
    "reference": "a1b2c3d4-e5f6...",
    "old_status": "pending",
    "new_status": "successful",
    "amount": 50000,
    "transaction_id": "7945454515",
    "message": "Payment completed successfully!",
    "callback_data": {
        "selcom_channel": "TIGOPESATZ",
        "selcom_result": "SUCCESS"
    },
    "updated_at": "2026-03-04T10:32:15.000Z"
}
```

## Environment Variables

```env
BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=database

PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

## Troubleshooting

### WebSocket server won't start

```bash
# Check if port is in use
lsof -i :6001
# Kill if needed
kill -9 <PID>
```

### Not receiving events

```bash
# Verify queue worker is running
php artisan queue:work

# Check logs
tail -f storage/logs/laravel.log
```

### Authentication fails

- Check Bearer token is valid
- Ensure user owns the payment
- Verify auth endpoint URL is correct

### Connection timeout

- Check firewall settings
- Verify WebSocket server is running
- Try different port if 6001 is blocked

## Production Checklist

- [ ] Set up Supervisor for WebSocket server
- [ ] Set up Supervisor for queue worker
- [ ] Configure nginx reverse proxy
- [ ] Enable SSL (use HTTPS and WSS)
- [ ] Set `PUSHER_SCHEME=https`
- [ ] Test with real Selcom callbacks
- [ ] Monitor logs and performance
- [ ] Set up error alerting

## Useful Commands

```bash
# Check WebSocket server status
ps aux | grep websockets

# Restart WebSocket server
php artisan websockets:restart

# Clear broadcasts from queue
php artisan queue:flush

# Monitor queue in realtime
php artisan queue:listen

# Test WebSocket connection
wscat -c ws://localhost:6001/app/local
```

## Resources

📖 **[Quick Setup](WEBSOCKET_SETUP.md)**  
📘 **[Full Documentation](docs/WEBSOCKET_PAYMENT_STATUS.md)**  
📗 **[Implementation Summary](WEBSOCKET_IMPLEMENTATION_SUMMARY.md)**  
📕 **[API Documentation](postman/README.md)**  
🧪 **[Test Tool](http://localhost:8000/websocket-test.html)**

## Support

**Check Logs**:

- `storage/logs/laravel.log`
- `storage/logs/websockets.log`

**Online Help**:

- Laravel Broadcasting: https://laravel.com/docs/broadcasting
- Laravel WebSockets: https://beyondco.de/docs/laravel-websockets
- Pusher JS: https://github.com/pusher/pusher-js

---

**Last Updated**: March 4, 2026  
**Quick Ref Version**: 1.0
