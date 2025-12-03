#!/bin/bash

# FinBoard Production Setup Script
# This script sets up the complete backend performance stack for production

set -e  # Exit on any error

echo "🚀 Starting FinBoard Production Setup..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root for system installations
if [[ $EUID -eq 0 ]]; then
    print_warning "Running as root - be careful!"
fi

# Detect OS
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="linux"
    PACKAGE_MANAGER="apt-get"
    if command -v dnf &> /dev/null; then
        PACKAGE_MANAGER="dnf"
    elif command -v yum &> /dev/null; then
        PACKAGE_MANAGER="yum"
    fi
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macos"
    PACKAGE_MANAGER="brew"
else
    print_error "Unsupported OS: $OSTYPE"
    exit 1
fi

print_status "Detected OS: $OS with package manager: $PACKAGE_MANAGER"

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 1. Install Redis
print_status "Checking Redis installation..."
if ! command_exists redis-server; then
    print_status "Installing Redis..."
    if [[ "$OS" == "linux" ]]; then
        sudo $PACKAGE_MANAGER update
        sudo $PACKAGE_MANAGER install -y redis-server
        sudo systemctl enable redis-server
        sudo systemctl start redis-server
    elif [[ "$OS" == "macos" ]]; then
        if ! command_exists brew; then
            print_error "Homebrew not found. Please install Homebrew first: https://brew.sh/"
            exit 1
        fi
        brew install redis
        brew services start redis
    fi
    print_success "Redis installed and started"
else
    print_success "Redis already installed"
fi

# Verify Redis is running
if redis-cli ping | grep -q "PONG"; then
    print_success "Redis is running"
else
    print_error "Redis is not responding"
    exit 1
fi

# 2. Install Node.js and npm (for Laravel Echo Server)
print_status "Checking Node.js installation..."
if ! command_exists node; then
    print_status "Installing Node.js..."
    if [[ "$OS" == "linux" ]]; then
        curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
        sudo $PACKAGE_MANAGER install -y nodejs
    elif [[ "$OS" == "macos" ]]; then
        brew install node
    fi
    print_success "Node.js installed"
else
    print_success "Node.js already installed"
fi

# 3. Install Laravel Echo Server globally
print_status "Installing Laravel Echo Server..."
if ! command_exists laravel-echo-server; then
    sudo npm install -g laravel-echo-server
    print_success "Laravel Echo Server installed"
else
    print_success "Laravel Echo Server already installed"
fi

# 4. Check PHP and Composer
print_status "Checking PHP and Composer..."
if ! command_exists php; then
    print_error "PHP not found. Please install PHP 8.1+ first"
    exit 1
fi

if ! command_exists composer; then
    print_error "Composer not found. Please install Composer first"
    exit 1
fi

print_success "PHP and Composer are available"

# 5. Install PHP Redis extension
print_status "Checking PHP Redis extension..."
if ! php -m | grep -q redis; then
    print_status "Installing PHP Redis extension..."
    if [[ "$OS" == "linux" ]]; then
        sudo $PACKAGE_MANAGER install -y php-redis
    elif [[ "$OS" == "macos" ]]; then
        print_warning "Please install php-redis manually on macOS"
        print_warning "For Homebrew PHP: brew install php@8.1-redis"
        print_warning "For other PHP installations, check your PHP setup"
    fi
else
    print_success "PHP Redis extension is available"
fi

# 6. Laravel project setup
print_status "Setting up Laravel project..."

# Check if we're in the project directory
if [[ ! -f "artisan" ]]; then
    print_error "Not in Laravel project directory. Please run this script from the FinBoard root directory"
    exit 1
fi

# Install PHP dependencies
print_status "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Copy environment file if it doesn't exist
if [[ ! -f ".env" ]]; then
    print_status "Creating .env file from .env.example..."
    cp .env.example .env
    print_warning "Please update .env file with your production settings"
fi

# Generate application key
print_status "Generating application key..."
php artisan key:generate

# Run database migrations
print_status "Running database migrations..."
php artisan migrate --force

# Clear and cache config
print_status "Optimizing Laravel for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Configure Laravel Echo Server
print_status "Configuring Laravel Echo Server..."
if [[ ! -f "laravel-echo-server.json" ]]; then
    cat > laravel-echo-server.json << EOF
{
    "authHost": "http://localhost:8000",
    "authEndpoint": "/broadcasting/auth",
    "clients": [
        {
            "appId": "local",
            "key": "local"
        }
    ],
    "database": "redis",
    "databaseConfig": {
        "redis": {},
        "sqlite": {
            "databasePath": "/database/laravel-echo-server.sqlite"
        }
    },
    "devMode": false,
    "host": null,
    "port": "6001",
    "protocol": "http",
    "socketio": {},
    "sslCertPath": "",
    "sslKeyPath": "",
    "sslCertChainPath": "",
    "sslPassphrase": "",
    "subscribers": {
        "http": true,
        "redis": true
    },
    "apiOriginAllow": {
        "allowCors": true,
        "allowOrigin": "*",
        "allowMethods": "GET, POST",
        "allowHeaders": "Origin, Content-Type, X-Auth-Token, X-Requested-With, Accept, Authorization, X-CSRF-TOKEN, X-Socket-Id"
    }
}
EOF
    print_success "Laravel Echo Server configured"
else
    print_success "Laravel Echo Server config already exists"
fi

# 8. Create startup scripts
print_status "Creating startup scripts..."

# Create start-production.sh
cat > start-production.sh << 'EOF'
#!/bin/bash
echo "Starting FinBoard Production Services..."

# Start Laravel application
echo "Starting Laravel application..."
php artisan serve --host=0.0.0.0 --port=8000 &
LARAVEL_PID=$!

# Start WebSocket server
echo "Starting WebSocket server..."
laravel-echo-server start &
ECHO_PID=$!

# Optional: Start queue worker
echo "Starting queue worker..."
php artisan queue:work --sleep=3 --tries=3 &
QUEUE_PID=$!

echo "Services started!"
echo "Laravel: http://localhost:8000"
echo "WebSocket: http://localhost:6001"
echo "Telescope: http://localhost:8000/telescope"
echo ""
echo "Press Ctrl+C to stop all services"

# Wait for interrupt
trap "echo 'Stopping services...'; kill $LARAVEL_PID $ECHO_PID $QUEUE_PID 2>/dev/null; exit" INT
wait
EOF

chmod +x start-production.sh

# Create stop-production.sh
cat > stop-production.sh << 'EOF'
#!/bin/bash
echo "Stopping FinBoard Production Services..."

# Stop Laravel
pkill -f "php artisan serve" || true

# Stop WebSocket server
pkill -f "laravel-echo-server" || true

# Stop queue worker
pkill -f "php artisan queue:work" || true

echo "All services stopped"
EOF

chmod +x stop-production.sh

# Create health-check.sh
cat > health-check.sh << 'EOF'
#!/bin/bash
echo "FinBoard Health Check"
echo "===================="

# Check Redis
echo -n "Redis: "
if redis-cli ping | grep -q "PONG"; then
    echo "✅ Running"
else
    echo "❌ Not responding"
fi

# Check Laravel
echo -n "Laravel: "
if curl -s http://localhost:8000/api/financial-highlights/dashboard > /dev/null; then
    echo "✅ Running"
else
    echo "❌ Not responding"
fi

# Check WebSocket
echo -n "WebSocket: "
if curl -s http://localhost:6001/socket.io/?EIO=4&transport=polling > /dev/null; then
    echo "✅ Running"
else
    echo "❌ Not responding"
fi

# Check Telescope
echo -n "Telescope: "
if curl -s http://localhost:8000/telescope > /dev/null; then
    echo "✅ Running"
else
    echo "❌ Not responding"
fi

echo ""
echo "Health check complete"
EOF

chmod +x health-check.sh

print_success "Startup scripts created"

# 9. Final instructions
print_success "🎉 FinBoard production setup complete!"
echo ""
echo "Next steps:"
echo "1. Update your .env file with production settings:"
echo "   - CACHE_STORE=redis"
echo "   - BROADCAST_CONNECTION=redis"
echo "   - TELESCOPE_ENABLED=true"
echo "   - Database credentials"
echo ""
echo "2. Start the application:"
echo "   ./start-production.sh"
echo ""
echo "3. Check service health:"
echo "   ./health-check.sh"
echo ""
echo "4. Access your application:"
echo "   - Dashboard: http://localhost:8000"
echo "   - API: http://localhost:8000/api/financial-highlights/dashboard"
echo "   - Monitoring: http://localhost:8000/telescope"
echo ""
echo "5. To stop services:"
echo "   ./stop-production.sh"
echo ""
print_warning "Remember to configure your web server (nginx/apache) for production deployment!"
