# Selcom Payments API - Postman Collection

Comprehensive API documentation for Selcom payment integration in the Sokolink marketplace platform.

## 📋 Overview

This Postman collection provides complete documentation for the Selcom payment system, including:

- **Checkout & Payment Initiation**: Process cart checkout and initiate mobile money payments
- **Payment Management**: View and track payment history
- **Webhooks**: Handle Selcom payment callbacks

## 🚀 Getting Started

### 1. Import the Collection

1. Open Postman
2. Click **Import** button
3. Select the `Selcom_Payments_API.postman_collection.json` file
4. The collection will be imported with all requests and examples

### 2. Configure Environment Variables

Set the following variables in your Postman environment or collection variables:

| Variable            | Description                     | Example                      |
| ------------------- | ------------------------------- | ---------------------------- |
| `base_url`          | Your API base URL               | `https://api.sokolink.co.tz` |
| `bearer_token`      | Authentication token from login | `1\|abc123...`               |
| `payment_reference` | Auto-populated after checkout   | `a1b2c3d4-e5f6...`           |

### 3. Authentication

Most endpoints require authentication using Laravel Sanctum Bearer token:

1. Login via the auth endpoints (see main API collection)
2. Copy the Bearer token from the response
3. Set it as `bearer_token` variable in your environment

## 📱 Payment Flow

```
┌─────────────┐
│ Add to Cart │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ POST /checkout  │ ◄── User initiates payment
└────────┬────────┘
         │
         ▼
┌───────────────────────┐
│ Selcom creates order  │
│ & sends USSD push     │
└───────────┬───────────┘
            │
            ▼
┌────────────────────────┐
│ User enters PIN        │
│ on mobile device       │
└───────────┬────────────┘
            │
            ▼
┌──────────────────────────────┐
│ POST /payments/callback/     │ ◄── Selcom webhook
│      selcom                  │
└──────────────┬───────────────┘
               │
               ▼
┌─────────────────────────┐
│ Order created          │
│ Payment confirmed      │
│ Notifications sent     │
└────────────────────────┘
```

## 🔌 API Endpoints

### Checkout & Payment

#### POST `/api/v1/checkout`

Initiates the checkout process. Creates an order and initiates Selcom mobile money payment.

**Authentication**: Required  
**User Role**: Any authenticated user with items in cart

**Request Body**:

```json
{
    "fullname": "John Doe",
    "phone": "+255712345678",
    "address_phone": "+255712345678",
    "address": "123 Main Street, Kinondoni",
    "region_id": 1,
    "payment_method_id": 1,
    "payment_option_id": 1
}
```

**Payment Options**:

- `1` = Pay Now (immediate payment)
- `2` = Save & Pay Later
- `3` = Request Payment

**Response**:

```json
{
    "status": true,
    "message": "Payment push initiated. Please complete the payment on your phone.",
    "code": 200,
    "data": {
        "reference": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "amount": 50000,
        "status": "pending"
    }
}
```

### Payment Management

#### GET `/api/v1/payments`

Get paginated list of all payments for authenticated user.

**Authentication**: Required

**Query Parameters**:

- `payment_method_id` (optional): Filter by payment method
- `status` (optional): Filter by status (pending, successful, failed, cancelled)
- `search` (optional): Search by order/payment reference

#### GET `/api/v1/payments/{id}`

Get detailed information about a specific payment.

**Authentication**: Required

**Path Parameters**:

- `id`: Payment ID

#### GET `/api/v1/payments/{reference}/selcom-status`

Query Selcom API directly to get the current payment status for a given payment reference.

**Authentication**: Required

**Path Parameters**:

- `reference`: Payment reference (order_id used in Selcom)

**Response Example**:

```json
{
    "status": true,
    "message": "Payment status retrieved successfully",
    "code": 200,
    "data": {
        "order_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "payment_status": "COMPLETED",
        "amount": 50000,
        "channel": "MPESATZ",
        "transid": "7945454515",
        "result": "SUCCESS",
        "resultcode": "000",
        "resultdesc": "Transaction successful",
        "phone": "255712345678",
        "local_status": "successful",
        "local_payment": {
            "id": 101,
            "status": "successful",
            "amount": "50000.00",
            "transaction_id": "7945454515",
            "created_at": "2026-03-04T10:30:00.000000Z",
            "updated_at": "2026-03-04T10:32:15.000000Z"
        }
    }
}
```

**Use Cases**:

- Check real-time payment status from Selcom
- Reconcile payment discrepancies
- Debug payment issues
- Verify payment completion status

### Webhooks

#### POST `/api/v1/payments/callback/selcom`

Selcom payment callback handler. Called automatically by Selcom.

**Authentication**: Not required (called by Selcom)

**Request Body** (from Selcom):

```json
{
    "result": "SUCCESS",
    "resultcode": "000",
    "order_id": "602021152",
    "transid": "7945454515",
    "reference": "856266164161",
    "channel": "TIGOPESATZ",
    "amount": "50000",
    "phone": "255712345678",
    "payment_status": "COMPLETED"
}
```

## 💳 Supported Payment Methods

Via Selcom Mobile Money Pull (USSD Push):

- **M-Pesa** (Vodacom Tanzania) - Channel: `MPESATZ`
- **Tigo Pesa** - Channel: `TIGOPESATZ`
- **Airtel Money** - Channel: `AIRTELTZ`
- **Halopesa** - Channel: `HALOPESATZ`

## � Real-Time Payment Updates via WebSocket

### Overview

After initiating a checkout, clients can subscribe to a private WebSocket channel to receive **real-time updates** when the payment status changes. This eliminates the need for polling and provides instant notifications.

### How It Works

1. **Checkout**: User initiates payment via `/checkout` endpoint
2. **WebSocket Info**: Response includes channel name and auth endpoint
3. **Subscribe**: Client connects to WebSocket and subscribes to the channel
4. **Real-Time Updates**: When Selcom sends callback, server broadcasts payment status
5. **Client Receives**: Instant notification with payment result

### WebSocket Response Data

After checkout, you'll receive WebSocket connection details:

```json
{
    "websocket": {
        "channel": "private-payment.{reference}",
        "event": "payment.status.updated",
        "auth_endpoint": "https://api.sokolink.co.tz/api/broadcasting/auth"
    }
}
```

### Quick Implementation

#### JavaScript/TypeScript Example

```javascript
import Pusher from "pusher-js";

// Initialize after checkout
const pusher = new Pusher("your_pusher_key", {
    cluster: "mt1",
    wsHost: "your-api-domain.com",
    wsPort: 6001,
    forceTLS: false,
    authEndpoint: "https://your-api.com/api/broadcasting/auth",
    auth: {
        headers: {
            Authorization: `Bearer ${yourBearerToken}`,
        },
    },
});

// Subscribe to payment channel
const channel = pusher.subscribe("private-payment." + paymentReference);

// Listen for updates
channel.bind("payment.status.updated", (data) => {
    console.log("Payment update:", data);

    if (data.new_status === "successful") {
        alert("Payment successful! " + data.message);
        // Navigate to success page
    } else if (data.new_status === "failed") {
        alert("Payment failed: " + data.message);
        // Show retry options
    }
});
```

### Event Data Structure

When payment status changes, you'll receive:

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
        "selcom_resultcode": "000"
    },
    "updated_at": "2026-03-04T10:32:15.000000Z",
    "message": "Payment completed successfully! Your order is being processed."
}
```

### Test WebSocket Connection

#### Using Browser Test Tool

1. Start WebSocket server: `php artisan websockets:serve`
2. Start queue worker: `php artisan queue:work`
3. Open: `http://localhost:8000/websocket-test.html`
4. Enter your Bearer token and payment reference
5. Click Connect
6. Simulate Selcom callback using Postman
7. Watch real-time updates appear!

#### Using Pusher Debug Console

- For Pusher Cloud: Use the Debug Console in your Pusher dashboard
- For Laravel WebSockets: Use the built-in dashboard at `/laravel-websockets`

### Setup Instructions

For complete WebSocket setup instructions, see:

- **Quick Setup**: `/WEBSOCKET_SETUP.md` in project root
- **Full Documentation**: `/docs/WEBSOCKET_PAYMENT_STATUS.md`

### Benefits

✅ **Instant updates** - No polling required  
✅ **Better UX** - Show real-time payment status  
✅ **Reduced API calls** - Server pushes updates  
✅ **Secure** - Private channels with authentication  
✅ **Scalable** - Handles thousands of concurrent connections

## �📊 Payment Statuses

| Status       | Description                             |
| ------------ | --------------------------------------- |
| `pending`    | Payment initiated but not yet completed |
| `successful` | Payment completed successfully          |
| `failed`     | Payment failed or was rejected          |
| `cancelled`  | Payment cancelled by user or system     |

## 🔐 Selcom Integration Details

### Environment Variables Required

Configure these in your `.env` file:

```env
# Selcom API Configuration
SELCOM_API_BASE_URL=https://apigw.selcommobile.com
SELCOM_API_KEY=your_api_key_here
SELCOM_API_SECRET=your_api_secret_here
SELCOM_VENDOR_TILL=your_vendor_till_number
SELCOM_ORDER_ENDPOINT=/v1/checkout/create-order
SELCOM_WALLET_PAYMENT_ENDPOINT=/v1/checkout/wallet-payment
SELCOM_WEBHOOK_URL=https://api.sokolink.co.tz/api/v1/payments/callback/selcom
```

### Webhook Configuration

Ensure your webhook URL is:

1. Publicly accessible (not localhost)
2. Using HTTPS
3. Base64 encoded when sent to Selcom (handled automatically by the system)

### Testing Webhooks Locally

For local development, use tools like:

- **ngrok**: `ngrok http 8000`
- **Expose**: `expose share http://localhost:8000`

Then update `SELCOM_WEBHOOK_URL` with the public URL.

## 📝 Sample Test Cases

### Test Case 1: Successful Payment

1. **Add items to cart** using cart endpoints
2. **Initiate checkout** with valid payment details
3. **Simulate payment approval** on mobile device (in production)
4. **Verify webhook callback** updates payment status
5. **Check order creation** in orders endpoint

### Test Case 2: Failed Payment

1. Initiate checkout
2. Reject payment on mobile device (or timeout)
3. Verify webhook marks payment as failed
4. Cart should remain intact for retry

### Test Case 3: Payment Lookup

1. Make a successful payment
2. Use GET `/payments` to list all payments
3. Use GET `/payments/{id}` to get specific payment details
4. Verify all fields are populated correctly

## 🐛 Troubleshooting

### Payment stuck in "pending" status

**Possible causes**:

- User hasn't approved on mobile device
- Selcom webhook not reaching your server
- Webhook URL not properly configured

**Solutions**:

- Check Selcom dashboard for payment status
- Verify webhook URL is accessible
- Check application logs for webhook calls

### Webhook not being received

**Possible causes**:

- Firewall blocking Selcom IP addresses
- SSL certificate issues
- Incorrect webhook URL configuration

**Solutions**:

- Whitelist Selcom IP ranges
- Ensure valid SSL certificate
- Verify webhook URL in Selcom merchant portal

### Phone number validation errors

**Issue**: "Mobile phone should be like +255XXXXXXXXX"

**Solution**: Ensure phone numbers follow the format:

- Must start with `+255`
- Followed by 9 digits
- Example: `+255712345678`

## 📞 Support

For Selcom-specific issues:

- **Selcom Documentation**: [https://developer.selcommobile.com](https://developer.selcommobile.com)
- **Selcom Support**: support@selcommobile.com

For Sokolink API issues:

- Check application logs in `storage/logs/laravel.log`
- Review Selcom callback logs (logged with each webhook call)

## 📄 Additional Resources

- [Selcom API Documentation](https://developer.selcommobile.com)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Postman Documentation](https://learning.postman.com/)

---

**Last Updated**: March 4, 2026  
**API Version**: v1  
**Collection Version**: 1.0.0
