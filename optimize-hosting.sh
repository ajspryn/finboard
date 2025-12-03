#!/bin/bash

# 🚀 FinBoard Hosting Quick Optimization Script
# Optimasi cepat untuk performa hosting environment

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[OPTIMIZE]${NC} $1"
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

echo "🚀 FinBoard Hosting Quick Optimization"
echo "======================================"

# Check if in Laravel project
if [[ ! -f "artisan" ]]; then
    print_error "Not in Laravel project directory"
    exit 1
fi

# 1. Clear all caches
print_status "Clearing all Laravel caches..."
php artisan cache:clear >/dev/null 2>&1
php artisan config:clear >/dev/null 2>&1
php artisan route:clear >/dev/null 2>&1
php artisan view:clear >/dev/null 2>&1
print_success "Caches cleared"

# 2. Optimize Composer
print_status "Optimizing Composer autoloader..."
if [[ -f "composer.json" ]]; then
    composer install --optimize-autoloader --no-dev --no-scripts >/dev/null 2>&1
    print_success "Composer optimized"
else
    print_warning "composer.json not found"
fi

# 3. Cache for production
print_status "Caching Laravel for production..."
php artisan config:cache >/dev/null 2>&1
php artisan route:cache >/dev/null 2>&1
php artisan view:cache >/dev/null 2>&1
print_success "Laravel cached for production"

# 4. Run migrations
print_status "Ensuring database is up to date..."
php artisan migrate --force >/dev/null 2>&1
print_success "Database migrations completed"

# 5. Set correct permissions
print_status "Setting correct file permissions..."
chmod -R 755 storage/ 2>/dev/null || true
chmod -R 755 bootstrap/cache/ 2>/dev/null || true

# Set ownership if running as root
if [[ $EUID -eq 0 ]]; then
    if id -u www-data >/dev/null 2>&1; then
        chown -R www-data:www-data storage/ 2>/dev/null || true
        chown -R www-data:www-data bootstrap/cache/ 2>/dev/null || true
        print_success "File ownership set to www-data"
    fi
fi

print_success "Permissions set"

# 6. Generate optimized autoload
print_status "Generating optimized autoload files..."
composer dump-autoload --optimize >/dev/null 2>&1
print_success "Autoload optimized"

# 7. Clear OPcache if available
print_status "Clearing PHP OPcache..."
if php -r "opcache_reset();" 2>/dev/null; then
    print_success "OPcache cleared"
else
    print_warning "OPcache not available or not enabled"
fi

# 8. Warm up critical caches
print_status "Warming up critical caches..."
php artisan tinker --execute="
Cache::rememberForever('app_optimized', function() { return true; });
echo 'Cache warmed up';
" >/dev/null 2>&1
print_success "Cache warmed up"

# 9. Check Redis
print_status "Checking Redis connection..."
if command -v redis-cli >/dev/null 2>&1 && redis-cli ping 2>/dev/null | grep -q "PONG"; then
    print_success "Redis is running"
    # Clear Redis cache to ensure fresh start
    redis-cli FLUSHDB >/dev/null 2>&1
    print_success "Redis cache cleared"
else
    print_warning "Redis not available - using file cache"
fi

# 10. Generate application key if missing
print_status "Ensuring application key exists..."
if ! grep -q "^APP_KEY=" .env 2>/dev/null || grep -q "^APP_KEY=$" .env 2>/dev/null; then
    php artisan key:generate >/dev/null 2>&1
    print_success "Application key generated"
else
    print_success "Application key exists"
fi

# 11. Check environment file
print_status "Checking environment configuration..."
if [[ ! -f ".env" ]]; then
    print_warning ".env file not found - copying from .env.example"
    cp .env.example .env 2>/dev/null || print_error "Could not create .env file"
fi

# Set production defaults if not set
if ! grep -q "^APP_ENV=production" .env; then
    sed -i.bak 's/^APP_ENV=.*/APP_ENV=production/' .env 2>/dev/null || true
    print_success "Set APP_ENV=production"
fi

if ! grep -q "^APP_DEBUG=false" .env; then
    sed -i.bak 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env 2>/dev/null || true
    print_success "Set APP_DEBUG=false"
fi

# 12. Check for common performance issues
print_status "Checking for performance issues..."

# Check if storage link exists
if [[ ! -L "public/storage" ]] && [[ -d "storage/app/public" ]]; then
    php artisan storage:link >/dev/null 2>&1
    print_success "Storage link created"
fi

# Check log file size
if [[ -f "storage/logs/laravel.log" ]]; then
    LOG_SIZE=$(stat -f%z storage/logs/laravel.log 2>/dev/null || stat -c%s storage/logs/laravel.log 2>/dev/null || echo "0")
    LOG_SIZE_MB=$((LOG_SIZE / 1024 / 1024))
    if [[ $LOG_SIZE_MB -gt 100 ]]; then
        print_warning "Log file is ${LOG_SIZE_MB}MB - consider rotating"
        echo "" > storage/logs/laravel.log
        print_success "Log file rotated"
    fi
fi

# 13. Final optimization check
print_status "Running final optimization checks..."

# Test basic Laravel functionality
if php artisan --version >/dev/null 2>&1; then
    print_success "Laravel is working"
else
    print_error "Laravel has issues"
fi

# Check if critical directories are writable
for dir in "storage/logs" "storage/framework/cache" "storage/framework/sessions" "storage/framework/views" "bootstrap/cache"; do
    if [[ ! -w "$dir" ]]; then
        print_error "Directory $dir is not writable"
    fi
done

print_success "Optimization checks completed"

echo ""
echo "🎉 OPTIMIZATION COMPLETE!"
echo "========================="
echo ""
echo "✅ Laravel caches cleared and rebuilt"
echo "✅ Composer autoloader optimized"
echo "✅ Database migrations run"
echo "✅ File permissions set"
echo "✅ Environment configured for production"
echo "✅ OPcache cleared"
echo "✅ Critical caches warmed up"
echo ""
echo "🚀 NEXT STEPS:"
echo "1. Restart your web server (nginx/apache)"
echo "2. Restart PHP-FPM if using it"
echo "3. Test your application"
echo "4. Run ./diagnose-hosting.sh to verify optimizations"
echo ""
echo "📊 MONITOR:"
echo "- Access Telescope: /telescope"
echo "- Check health: ./health-check.sh"
echo "- Monitor logs: tail -f storage/logs/laravel.log"
echo ""
echo "📖 DETAILED GUIDE:"
echo "See HOSTING_PERFORMANCE_GUIDE.md for advanced optimizations"
