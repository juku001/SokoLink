# WebSocket Real-Time Payment Status Updates

This document explains how to implement real-time payment status updates using WebSockets in the Sokolink payment system.

## Overview

After initiating a payment checkout, clients can subscribe to a private WebSocket channel to receive real-time updates when Selcom sends the payment callback. This eliminates the need for polling and provides instant notification of payment completion, failure, or cancellation.

## Flow Diagram

```
┌──────────────┐
│   Client     │
│  (Mobile/Web)│
└──────┬───────┘
       │
       │ 1. POST /checkout
       ▼
┌──────────────────┐
│  Laravel API     │
│  - Creates       │
│    payment       │
│  - Returns ref   │
└──────┬───────────┘
       │
       │ 2. WebSocket connection info
       ▼
┌──────────────────┐
│   Client subs    │
│   to channel:    │
│   payment.{ref}  │
└──────┬───────────┘
       │
       │ 3. User approves on phone
       │
       ▼ 4. Selcom callback
┌──────────────────┐
│  Laravel API     │
│  - Updates       │
│    payment       │
│  - Broadcasts    │
│    event         │
└──────┬───────────┘
       │
       │ 5. WebSocket push
       ▼
┌──────────────────┐
│   Client         │
│  receives update │
│  - Shows success │
│  - Navigates     │
└──────────────────┘
```

## Server Setup

### 1. Install Required Packages

Choose one of the following options:

#### Option A: Laravel WebSockets (Self-hosted, Recommended)

```bash
composer require beyondcode/laravel-websockets
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
php artisan migrate
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
```

Install Pusher PHP SDK (required even for self-hosted):

```bash
composer require pusher/pusher-php-server
```

#### Option B: Pusher (Cloud-hosted)

```bash
composer require pusher/pusher-php-server
```

### 2. Configure Environment Variables

Add these to your `.env` file:

#### For Laravel WebSockets (Self-hosted):

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1

# Laravel WebSockets specific
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

#### For Pusher Cloud:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

### 3. Start WebSocket Server (for Laravel WebSockets only)

```bash
php artisan websockets:serve
```

Or run in background:

```bash
nohup php artisan websockets:serve > storage/logs/websockets.log 2>&1 &
```

For production, use Supervisor:

```ini
[program:websockets]
command=php /path/to/your/project/artisan websockets:serve
numprocs=1
autostart=true
autorestart=true
user=www-data
```

### 4. Configure Queue Worker (Important!)

Broadcasting events should be queued for better performance:

```bash
php artisan queue:work --queue=default,broadcast
```

Update `.env`:

```env
QUEUE_CONNECTION=redis  # or database
```

## Client Implementation

### Authentication

Clients must authenticate with the broadcasting system before subscribing to private channels.

#### Step 1: Get Auth Token

First, login to get your Sanctum Bearer token:

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "phone": "+255712345678",
  "password": "secret"
}
```

Response:

```json
{
    "status": true,
    "data": {
        "token": "1|abc123def456..."
    }
}
```

### JavaScript/TypeScript Implementation

#### Install Pusher JS Library

```bash
npm install pusher-js
# or
yarn add pusher-js
```

#### Example: React Native / JavaScript

```javascript
import Pusher from "pusher-js";

// Initialize Pusher client
const pusher = new Pusher("local", {
    // Use your PUSHER_APP_KEY
    cluster: "mt1", // Your PUSHER_APP_CLUSTER
    wsHost: "your-api-domain.com", // Your server domain
    wsPort: 6001, // WebSocket port (6001 for Laravel WebSockets)
    wssPort: 6001, // WSS port (if using HTTPS)
    forceTLS: false, // Set to true for production with HTTPS
    encrypted: true,
    disableStats: true,
    enabledTransports: ["ws", "wss"],
    authEndpoint: "https://your-api-domain.com/api/broadcasting/auth",
    auth: {
        headers: {
            Authorization: `Bearer ${yourBearerToken}`,
            Accept: "application/json",
        },
    },
});

// Function to monitor payment status
function subscribeToPaymentUpdates(paymentReference, onUpdate) {
    const channelName = `private-payment.${paymentReference}`;
    const channel = pusher.subscribe(channelName);

    // Handle successful subscription
    channel.bind("pusher:subscription_succeeded", () => {
        console.log("Successfully subscribed to payment updates");
    });

    // Handle subscription errors
    channel.bind("pusher:subscription_error", (error) => {
        console.error("Subscription error:", error);
    });

    // Listen for payment status updates
    channel.bind("payment.status.updated", (data) => {
        console.log("Payment status updated:", data);
        onUpdate(data);
    });

    // Return unsubscribe function
    return () => {
        channel.unbind_all();
        pusher.unsubscribe(channelName);
    };
}

// Usage example
async function processCheckout(checkoutData) {
    // 1. Initiate checkout
    const checkoutResponse = await fetch(
        "https://your-api.com/api/v1/checkout",
        {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${bearerToken}`,
            },
            body: JSON.stringify(checkoutData),
        },
    );

    const result = await checkoutResponse.json();

    if (result.status) {
        const { reference, websocket } = result.data;

        // 2. Subscribe to WebSocket updates
        const unsubscribe = subscribeToPaymentUpdates(reference, (data) => {
            // Handle payment status update
            switch (data.new_status) {
                case "successful":
                    alert("Payment successful! " + data.message);
                    // Navigate to success page or show order details
                    navigateToOrderConfirmation(data.payment_id);
                    break;

                case "failed":
                    alert("Payment failed: " + data.message);
                    // Show retry options
                    showRetryPaymentDialog();
                    break;

                case "cancelled":
                    alert("Payment cancelled");
                    break;

                case "pending":
                    console.log("Payment still pending...");
                    break;
            }

            // Optionally unsubscribe after receiving final status
            if (
                ["successful", "failed", "cancelled"].includes(data.new_status)
            ) {
                unsubscribe();
            }
        });

        // Show loading state with payment instructions
        showPaymentPendingDialog(result.data.message);
    }
}
```

#### Example: React Hook

```typescript
import { useEffect } from "react";
import Pusher from "pusher-js";

interface PaymentUpdate {
    payment_id: number;
    reference: string;
    old_status: string;
    new_status: string;
    amount: number;
    transaction_id: string | null;
    callback_data: any;
    updated_at: string;
    message: string;
}

export function usePaymentStatusUpdates(
    paymentReference: string | null,
    bearerToken: string,
    onUpdate: (data: PaymentUpdate) => void,
) {
    useEffect(() => {
        if (!paymentReference || !bearerToken) return;

        const pusher = new Pusher(process.env.REACT_APP_PUSHER_KEY!, {
            cluster: process.env.REACT_APP_PUSHER_CLUSTER!,
            wsHost: process.env.REACT_APP_WS_HOST!,
            wsPort: parseInt(process.env.REACT_APP_WS_PORT || "6001"),
            forceTLS: process.env.REACT_APP_WS_TLS === "true",
            encrypted: true,
            authEndpoint: `${process.env.REACT_APP_API_URL}/api/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${bearerToken}`,
                    Accept: "application/json",
                },
            },
        });

        const channel = pusher.subscribe(`private-payment.${paymentReference}`);

        channel.bind("payment.status.updated", onUpdate);

        return () => {
            channel.unbind_all();
            pusher.unsubscribe(`private-payment.${paymentReference}`);
            pusher.disconnect();
        };
    }, [paymentReference, bearerToken, onUpdate]);
}

// Usage
function CheckoutPage() {
    const [paymentRef, setPaymentRef] = useState<string | null>(null);
    const { token } = useAuth();

    usePaymentStatusUpdates(paymentRef, token, (update) => {
        if (update.new_status === "successful") {
            toast.success(update.message);
            navigate(`/orders/${update.payment_id}`);
        } else if (update.new_status === "failed") {
            toast.error(update.message);
        }
    });

    // ... rest of component
}
```

#### Example: Flutter/Dart

```dart
import 'package:pusher_client/pusher_client.dart';

class PaymentWebSocketService {
  late PusherClient pusher;
  Channel? paymentChannel;

  void initialize(String bearerToken) {
    pusher = PusherClient(
      'local', // PUSHER_APP_KEY
      PusherOptions(
        host: 'your-api-domain.com',
        wsPort: 6001,
        encrypted: true,
        auth: PusherAuth(
          'https://your-api-domain.com/api/broadcasting/auth',
          headers: {
            'Authorization': 'Bearer $bearerToken',
            'Accept': 'application/json',
          },
        ),
      ),
      autoConnect: false,
    );
  }

  void subscribeToPayment(String reference, Function(dynamic) onUpdate) {
    pusher.connect();

    paymentChannel = pusher.subscribe('private-payment.$reference');

    paymentChannel?.bind('payment.status.updated', (event) {
      print('Payment update: ${event?.data}');
      onUpdate(event?.data);
    });
  }

  void unsubscribe() {
    if (paymentChannel != null) {
      pusher.unsubscribe('private-payment.${paymentChannel!.name}');
    }
    pusher.disconnect();
  }
}
```

## Event Data Structure

When a payment status update is broadcast, the following data is sent:

```json
{
    "payment_id": 101,
    "reference": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "old_status": "pending",
    "new_status": "successful",
    "amount": 50000,
    "transaction_id": "7945454515",
    "callback_data": {
        "selcom_order_id": "602021152",
        "selcom_transid": "7945454515",
        "selcom_channel": "TIGOPESATZ",
        "selcom_result": "SUCCESS",
        "selcom_resultcode": "000",
        "callback_received_at": "2026-03-04T10:32:15.000Z"
    },
    "updated_at": "2026-03-04T10:32:15.000000Z",
    "message": "Payment completed successfully! Your order is being processed."
}
```

## Testing

### Test WebSocket Connection

#### Using Postman

1. Create new WebSocket request
2. Connect to: `ws://your-domain.com:6001/app/local?protocol=7`
3. Subscribe to channel:

```json
{
    "event": "pusher:subscribe",
    "data": {
        "channel": "private-payment.YOUR_PAYMENT_REFERENCE",
        "auth": "YOUR_AUTH_SIGNATURE"
    }
}
```

#### Using wscat

```bash
npm install -g wscat
wscat -c ws://localhost:6001/app/local
```

### Simulate Payment Callback

Use the Postman collection to send a test callback:

```bash
curl -X POST http://localhost:8000/api/v1/payments/callback/selcom \
  -H "Content-Type: application/json" \
  -d '{
    "result": "SUCCESS",
    "resultcode": "000",
    "order_id": "602021152",
    "transid": "7945454515",
    "reference": "YOUR_PAYMENT_REFERENCE",
    "channel": "TIGOPESATZ",
    "amount": "50000",
    "phone": "255712345678",
    "payment_status": "COMPLETED"
  }'
```

## Troubleshooting

### Connection Issues

**Problem**: Cannot connect to WebSocket server

**Solutions**:

- Verify WebSocket server is running: `php artisan websockets:serve`
- Check firewall allows connections on port 6001
- Verify PUSHER\_\* environment variables are set correctly
- Check Laravel logs: `tail -f storage/logs/laravel.log`

### Authentication Fails

**Problem**: `pusher:subscription_error` when subscribing

**Solutions**:

- Ensure Bearer token is valid and not expired
- Check `routes/channels.php` authorization logic
- Verify user owns the payment being monitored
- Check `authEndpoint` URL is correct

### Events Not Received

**Problem**: Subscribed but not receiving updates

**Solutions**:

- Verify queue worker is running: `php artisan queue:work`
- Check broadcasting driver is set: `BROADCAST_DRIVER=pusher`
- Ensure event implements `ShouldBroadcast`
- Check WebSocket server logs for errors

### CORS Issues

**Problem**: Authentication endpoint blocked by CORS

**Solutions**:
Add to `config/cors.php`:

```php
'paths' => [
    'api/*',
    'sanctum/csrf-cookie',
    'broadcasting/auth',
],
```

## Security Considerations

1. **Channel Authorization**: Only payment owner can subscribe to their payment channel
2. **Token Expiry**: Implement token refresh mechanism for long-running connections
3. **SSL/TLS**: Always use WSS (encrypted WebSocket) in production
4. **Rate Limiting**: Implement rate limiting on broadcasting auth endpoint
5. **Timeout**: Set reasonable timeouts for payment monitoring (e.g., 5-10 minutes)

## Production Deployment

### Using Laravel WebSockets

1. **Use Supervisor** to keep WebSocket server running:

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

2. **Configure nginx** as reverse proxy:

```nginx
location /socket.io {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
}
```

3. **Enable SSL**:

```env
PUSHER_SCHEME=https
```

### Using Pusher Cloud

Simply update `.env` with your Pusher credentials. No additional server setup required.

## Performance Tips

1. **Unsubscribe**: Always unsubscribe when payment is complete or user leaves page
2. **Connection Pooling**: Reuse Pusher instance across your application
3. **Queue Events**: Ensure broadcasting events are queued for async processing
4. **Monitor**: Use Laravel Telescope or Horizon to monitor queue jobs

## Resources

- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel WebSockets Documentation](https://beyondco.de/docs/laravel-websockets)
- [Pusher Documentation](https://pusher.com/docs)
- [Pusher JS Client](https://github.com/pusher/pusher-js)

---

**Last Updated**: March 4, 2026
