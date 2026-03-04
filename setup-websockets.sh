#!/bin/bash

# WebSocket Setup Script for Sokolink Payments
# This script automates the installation and configuration of Laravel WebSockets

set -e  # Exit on error

echo "🚀 Sokolink Payment WebSocket Setup"
echo "===================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to print colored messages
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed. Please install Composer first."
    exit 1
fi

print_success "Composer found"

# Step 1: Install Laravel WebSockets
echo ""
echo "📦 Step 1: Installing Laravel WebSockets package..."
composer require beyondcode/laravel-websockets --no-interaction
if [ $? -eq 0 ]; then
    print_success "Laravel WebSockets installed"
else
    print_error "Failed to install Laravel WebSockets"
    exit 1
fi

# Step 2: Install Pusher PHP SDK
echo ""
echo "📦 Step 2: Installing Pusher PHP SDK..."
composer require pusher/pusher-php-server --no-interaction
if [ $? -eq 0 ]; then
    print_success "Pusher PHP SDK installed"
else
    print_error "Failed to install Pusher PHP SDK"
    exit 1
fi

# Step 3: Publish migrations
echo ""
echo "📝 Step 3: Publishing WebSockets migrations..."
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations" --force
print_success "Migrations published"

# Step 4: Run migrations
echo ""
echo "🗄️  Step 4: Running migrations..."
php artisan migrate
if [ $? -eq 0 ]; then
    print_success "Migrations completed"
else
    print_warning "Some migrations may have failed. Check manually."
fi

# Step 5: Publish config
echo ""
echo "⚙️  Step 5: Publishing WebSockets configuration..."
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config" --force
print_success "Configuration published"

# Step 6: Check .env file
echo ""
echo "🔧 Step 6: Checking environment configuration..."

if grep -q "BROADCAST_DRIVER=pusher" .env; then
    print_success "BROADCAST_DRIVER already set to pusher"
else
    print_warning "Adding BROADCAST_DRIVER to .env"
    echo "" >> .env
    echo "# Broadcasting / WebSocket Configuration" >> .env
    echo "BROADCAST_DRIVER=pusher" >> .env
fi

if grep -q "PUSHER_APP_ID=" .env; then
    print_success "Pusher configuration found in .env"
else
    print_warning "Adding Pusher configuration to .env"
    echo "PUSHER_APP_ID=local" >> .env
    echo "PUSHER_APP_KEY=local" >> .env
    echo "PUSHER_APP_SECRET=local" >> .env
    echo "PUSHER_APP_CLUSTER=mt1" >> .env
    echo "PUSHER_HOST=127.0.0.1" >> .env
    echo "PUSHER_PORT=6001" >> .env
    echo "PUSHER_SCHEME=http" >> .env
    print_success "Pusher configuration added to .env"
fi

# Step 7: Clear cache
echo ""
echo "🧹 Step 7: Clearing application cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
print_success "Cache cleared"

# Step 8: Create supervisor config (optional)
echo ""
read -p "📋 Do you want to create a Supervisor config file? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    SUPERVISOR_CONF="websockets-supervisor.conf"

    cat > $SUPERVISOR_CONF << 'EOF'
[program:websockets]
command=php /path/to/your/project/artisan websockets:serve
directory=/path/to/your/project
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/websockets.log
EOF

    print_success "Supervisor config created: $SUPERVISOR_CONF"
    print_warning "Remember to:"
    print_warning "  1. Update paths in $SUPERVISOR_CONF"
    print_warning "  2. Copy to /etc/supervisor/conf.d/"
    print_warning "  3. Run: sudo supervisorctl reread && sudo supervisorctl update"
fi

# Step 9: Create systemd service (optional)
echo ""
read -p "🔧 Do you want to create a systemd service file? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    SYSTEMD_SERVICE="websockets.service"

    cat > $SYSTEMD_SERVICE << 'EOF'
[Unit]
Description=Laravel WebSockets
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php /path/to/your/project/artisan websockets:serve
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

    print_success "Systemd service created: $SYSTEMD_SERVICE"
    print_warning "Remember to:"
    print_warning "  1. Update paths in $SYSTEMD_SERVICE"
    print_warning "  2. Copy to /etc/systemd/system/"
    print_warning "  3. Run: sudo systemctl enable websockets && sudo systemctl start websockets"
fi

# Final instructions
echo ""
echo "✅ Installation Complete!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Next Steps:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "1️⃣  Start the WebSocket server:"
echo "   php artisan websockets:serve"
echo ""
echo "2️⃣  Start the queue worker (in another terminal):"
echo "   php artisan queue:work"
echo ""
echo "3️⃣  Test the WebSocket connection:"
echo "   Open: http://localhost:8000/websocket-test.html"
echo ""
echo "4️⃣  Make a payment and watch real-time updates!"
echo ""
echo "📚 Documentation:"
echo "   - Quick Setup: WEBSOCKET_SETUP.md"
echo "   - Full Guide: docs/WEBSOCKET_PAYMENT_STATUS.md"
echo "   - Summary: WEBSOCKET_IMPLEMENTATION_SUMMARY.md"
echo ""
echo "🧪 Test Tool:"
echo "   http://localhost:8000/websocket-test.html"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
print_success "Setup completed successfully! 🎉"
echo ""
