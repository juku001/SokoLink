# Sokolink Backend API

Sokolink is a comprehensive e-commerce marketplace platform built with Laravel, featuring real-time payment processing via Selcom and WebSocket-based status updates.

## 🚀 Features

- **E-commerce Marketplace**: Complete marketplace with products, orders, and payments
- **Selcom Payment Integration**: Mobile money payments (M-Pesa, Tigo Pesa, Airtel Money, Halopesa)
- **Real-time WebSocket Updates**: Instant payment status notifications
- **Multi-vendor Support**: Sellers can manage stores and products
- **Academy Module**: Educational content for sellers
- **Contact Management**: CRM features for sellers
- **Comprehensive Reports**: Sales, expenses, and performance analytics
- **Admin Dashboard**: Platform management and monitoring

## 📋 Requirements

- PHP 8.1 or higher
- Composer
- MySQL/PostgreSQL/SQLite
- Node.js & NPM (for frontend assets)
- Redis (recommended for queues and caching)

## 🔧 Installation

### 1. Clone Repository

```bash
git clone <repository-url>
cd sokolink-backend
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup

```bash
php artisan migrate
php artisan db:seed  # Optional: seed with sample data
```

### 5. WebSocket Setup (Real-time Payment Updates)

#### Quick Setup

```bash
./setup-websockets.sh
```

#### Manual Setup

```bash
composer require beyondcode/laravel-websockets
composer require pusher/pusher-php-server
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider"
php artisan migrate
```

See [WEBSOCKET_SETUP.md](WEBSOCKET_SETUP.md) for detailed instructions.

## 🎯 Running the Application

### Development

```bash
# Start Laravel server
php artisan serve

# Start WebSocket server (for real-time updates)
php artisan websockets:serve

# Start queue worker (in another terminal)
php artisan queue:work
```

### Production

Use Supervisor or systemd to manage the WebSocket server and queue workers. See [WEBSOCKET_SETUP.md](WEBSOCKET_SETUP.md) for production deployment instructions.

## 📚 Documentation

### Payment System

- **[Postman Collection](postman/Selcom_Payments_API.postman_collection.json)** - API documentation
- **[Payment API Guide](postman/README.md)** - Complete payment flow documentation
- **[WebSocket Setup](WEBSOCKET_SETUP.md)** - Quick WebSocket setup guide
- **[WebSocket Documentation](docs/WEBSOCKET_PAYMENT_STATUS.md)** - Comprehensive WebSocket guide
- **[Implementation Summary](WEBSOCKET_IMPLEMENTATION_SUMMARY.md)** - WebSocket feature overview

### Testing

- **[WebSocket Test Tool](public/websocket-test.html)** - Browser-based WebSocket testing
    - Access at: `http://localhost:8000/websocket-test.html`

## 🔌 Real-Time Payment Updates

### How It Works

1. User initiates checkout → receives payment reference
2. Client subscribes to WebSocket channel: `private-payment.{reference}`
3. User approves payment on mobile device
4. Selcom sends callback to API
5. API broadcasts `PaymentStatusUpdated` event
6. Client receives real-time update instantly!

### Quick Implementation

```javascript
import Pusher from "pusher-js";

const pusher = new Pusher("local", {
    cluster: "mt1",
    wsHost: "localhost",
    wsPort: 6001,
    authEndpoint: "http://localhost:8000/api/broadcasting/auth",
    auth: {
        headers: {
            Authorization: `Bearer ${token}`,
        },
    },
});

const channel = pusher.subscribe(`private-payment.${reference}`);

channel.bind("payment.status.updated", (data) => {
    if (data.new_status === "successful") {
        alert("Payment successful!");
    }
});
```

## 🧪 Testing

### Run Tests

```bash
php artisan test
```

### Test WebSocket Connection

1. Open: `http://localhost:8000/websocket-test.html`
2. Enter your Bearer token and payment reference
3. Click Connect
4. Simulate a payment callback using Postman
5. Watch real-time updates!

## 📡 API Endpoints

### Base URL

```
http://localhost:8000/api/v1
```

### Main Endpoints

#### Authentication

- `POST /auth/register` - Register user
- `POST /auth/login` - Login
- `POST /auth/logout` - Logout
- `GET /auth/me` - Get authenticated user

#### Payments

- `POST /checkout` - Initiate payment
- `GET /payments` - List payments
- `GET /payments/{id}` - Get payment details
- `POST /payments/callback/selcom` - Selcom webhook (no auth)

#### Products & Marketplace

- `GET /products` - List products
- `POST /products` - Create product (seller)
- `GET /stores` - List stores
- `POST /orders` - Create order

See [postman/README.md](postman/README.md) for complete API documentation.

## 🔐 Environment Variables

### Essential Variables

```env
APP_NAME=Sokolink
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sokolink
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher
QUEUE_CONNECTION=database

# WebSocket Configuration
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

# Selcom Payment Gateway
SELCOM_API_BASE_URL=https://apigw.selcommobile.com
SELCOM_API_KEY=your_api_key
SELCOM_API_SECRET=your_api_secret
SELCOM_VENDOR_TILL=your_vendor_till
SELCOM_ORDER_ENDPOINT=/v1/checkout/create-order
SELCOM_WALLET_PAYMENT_ENDPOINT=/v1/checkout/wallet-payment
SELCOM_WEBHOOK_URL=https://your-domain.com/api/v1/payments/callback/selcom
```

## 🏗️ Project Structure

```
sokolink-backend/
├── app/
│   ├── Events/
│   │   └── PaymentStatusUpdated.php    # WebSocket event
│   ├── Helpers/
│   │   ├── PaymentHelper.php           # Payment processing
│   │   └── ResponseHelper.php          # API responses
│   ├── Http/Controllers/
│   │   ├── PaymentController.php       # Payment endpoints
│   │   ├── OrderController.php         # Order management
│   │   └── ProductController.php       # Product management
│   ├── Models/
│   └── Providers/
│       └── BroadcastServiceProvider.php # WebSocket provider
├── config/
│   └── broadcasting.php                 # Broadcasting config
├── routes/
│   ├── api.php                         # Main API routes
│   ├── channels.php                    # WebSocket channels
│   └── api/v1/                         # Versioned routes
├── docs/
│   └── WEBSOCKET_PAYMENT_STATUS.md     # WebSocket docs
├── postman/
│   ├── Selcom_Payments_API.postman_collection.json
│   └── README.md
├── public/
│   └── websocket-test.html             # Test tool
├── WEBSOCKET_SETUP.md                  # Setup guide
├── WEBSOCKET_IMPLEMENTATION_SUMMARY.md # Feature summary
└── setup-websockets.sh                 # Setup script
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

## 📄 License

This project is proprietary software. All rights reserved.

## 🆘 Support

### Documentation

- [WebSocket Setup Guide](WEBSOCKET_SETUP.md)
- [WebSocket Full Documentation](docs/WEBSOCKET_PAYMENT_STATUS.md)
- [Payment API Documentation](postman/README.md)

### Tools

- [WebSocket Test Tool](http://localhost:8000/websocket-test.html)
- [Laravel WebSockets Dashboard](http://localhost:8000/laravel-websockets)

### Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# WebSocket logs
tail -f storage/logs/websockets.log

# Queue jobs
php artisan queue:failed
```

## 🎉 What's New

### v1.1.0 - Real-Time Payment Updates (March 4, 2026)

- ✅ WebSocket integration for real-time payment status updates
- ✅ Private channel authentication for secure updates
- ✅ Event broadcasting on payment status changes
- ✅ Browser-based WebSocket test tool
- ✅ Comprehensive documentation and guides
- ✅ Automated setup script
- ✅ Production deployment instructions

See [WEBSOCKET_IMPLEMENTATION_SUMMARY.md](WEBSOCKET_IMPLEMENTATION_SUMMARY.md) for details.

---

**Built with ❤️ using Laravel**

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
