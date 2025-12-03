#!/bin/bash

# 🚀 FinBoard Hosting Performance Diagnostic Script
# Diagnosis lengkap performa aplikasi di hosting environment

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Functions
print_header() {
    echo -e "${CYAN}================================================${NC}"
    echo -e "${CYAN}  🚀 FinBoard Hosting Performance Diagnostic${NC}"
    echo -e "${CYAN}================================================${NC}"
    echo ""
}

print_section() {
    echo -e "${BLUE}[$1]${NC} $2"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${PURPLE}ℹ️  $1${NC}"
}

# Check if Laravel project
check_project() {
    print_section "PROJECT" "Checking Laravel project..."
    if [[ ! -f "artisan" ]]; then
        print_error "Not in Laravel project directory"
        exit 1
    fi
    print_success "Laravel project detected"
}

# Check PHP configuration
check_php() {
    print_section "PHP" "Checking PHP configuration..."

    # PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    print_info "PHP Version: $PHP_VERSION"

    # Memory limit
    MEMORY_LIMIT=$(php -r "echo ini_get('memory_limit');")
    if [[ "$MEMORY_LIMIT" == "128M" ]] || [[ "$MEMORY_LIMIT" == "256M" ]] || [[ "$MEMORY_LIMIT" == "512M" ]]; then
        print_success "Memory Limit: $MEMORY_LIMIT"
    else
        print_warning "Memory Limit: $MEMORY_LIMIT (recommended: 256M+)"
    fi

    # Max execution time
    MAX_EXEC=$(php -r "echo ini_get('max_execution_time');")
    if [[ "$MAX_EXEC" -ge 30 ]]; then
        print_success "Max Execution Time: ${MAX_EXEC}s"
    else
        print_warning "Max Execution Time: ${MAX_EXEC}s (recommended: 30s+)"
    fi

    # OPcache
    OPCACHE=$(php -r "echo extension_loaded('Zend OPcache') ? 'Enabled' : 'Disabled';")
    if [[ "$OPCACHE" == "Enabled" ]]; then
        print_success "OPcache: $OPCACHE"
    else
        print_error "OPcache: $OPCACHE (CRITICAL: Enable OPcache for production)"
    fi

    # Required extensions
    EXTENSIONS=("pdo" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath")
    for ext in "${EXTENSIONS[@]}"; do
        if php -m | grep -q "$ext"; then
            print_success "Extension $ext: Loaded"
        else
            print_error "Extension $ext: Missing"
        fi
    done
}

# Check environment configuration
check_environment() {
    print_section "ENVIRONMENT" "Checking environment configuration..."

    if [[ ! -f ".env" ]]; then
        print_error ".env file not found"
        return 1
    fi

    # Check critical environment variables
    ENV_VARS=("APP_ENV" "APP_DEBUG" "DB_CONNECTION" "CACHE_STORE" "SESSION_DRIVER")
    for var in "${ENV_VARS[@]}"; do
        if grep -q "^$var=" .env; then
            VALUE=$(grep "^$var=" .env | cut -d'=' -f2)
            print_success "$var: $VALUE"
        else
            print_warning "$var: Not set"
        fi
    done

    # Check production settings
    APP_ENV=$(grep "^APP_ENV=" .env | cut -d'=' -f2)
    APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d'=' -f2)

    if [[ "$APP_ENV" == "production" ]]; then
        print_success "Environment: Production mode"
    else
        print_warning "Environment: $APP_ENV (recommended: production)"
    fi

    if [[ "$APP_DEBUG" == "false" ]]; then
        print_success "Debug mode: Disabled"
    else
        print_warning "Debug mode: $APP_DEBUG (recommended: false in production)"
    fi
}

# Check database connection
check_database() {
    print_section "DATABASE" "Checking database connection..."

    if ! php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Connected'; } catch(Exception \$e) { echo 'Failed: '.\$e->getMessage(); }" | grep -q "Connected"; then
        print_error "Database connection failed"
        return 1
    fi
    print_success "Database connection successful"

    # Check if migrations are run
    if php artisan migrate:status | grep -q "Ran?"; then
        print_success "Database migrations: Up to date"
    else
        print_warning "Database migrations: Need to run"
    fi

    # Check performance indexes
    INDEX_COUNT=$(php artisan tinker --execute="echo count(DB::select(\"SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_%'\"));" 2>/dev/null || echo "0")
    if [[ "$INDEX_COUNT" -gt 0 ]]; then
        print_success "Performance indexes: $INDEX_COUNT found"
    else
        print_error "Performance indexes: None found (run migration)"
    fi
}

# Check Redis/Cache
check_cache() {
    print_section "CACHE" "Checking cache configuration..."

    CACHE_STORE=$(grep "^CACHE_STORE=" .env | cut -d'=' -f2)

    if [[ "$CACHE_STORE" == "redis" ]]; then
        print_info "Cache store: Redis"

        # Check Redis connection
        if command -v redis-cli >/dev/null 2>&1; then
            if redis-cli ping 2>/dev/null | grep -q "PONG"; then
                print_success "Redis connection: OK"

                # Redis info
                REDIS_MEMORY=$(redis-cli info memory | grep used_memory_human | cut -d: -f2)
                print_info "Redis memory usage: $REDIS_MEMORY"

                CACHE_KEYS=$(redis-cli keys "financial:*" | wc -l)
                print_info "Cache keys: $CACHE_KEYS"
            else
                print_error "Redis connection: Failed"
            fi
        else
            print_warning "redis-cli not found"
        fi
    else
        print_warning "Cache store: $CACHE_STORE (recommended: redis for production)"
    fi
}

# Check Laravel caching
check_laravel_cache() {
    print_section "LARAVEL" "Checking Laravel caching..."

    # Check if caches exist
    if [[ -f "bootstrap/cache/config.php" ]]; then
        print_success "Config cache: Exists"
    else
        print_warning "Config cache: Missing (run: php artisan config:cache)"
    fi

    if [[ -f "bootstrap/cache/routes.php" ]]; then
        print_success "Route cache: Exists"
    else
        print_warning "Route cache: Missing (run: php artisan route:cache)"
    fi

    if [[ -d "storage/framework/views" ]] && [[ $(ls storage/framework/views/*.php 2>/dev/null | wc -l) -gt 0 ]]; then
        print_success "View cache: Exists"
    else
        print_warning "View cache: Missing (run: php artisan view:cache)"
    fi
}

# Check file permissions
check_permissions() {
    print_section "PERMISSIONS" "Checking file permissions..."

    # Storage permissions
    if [[ -w "storage/" ]]; then
        print_success "Storage directory: Writable"
    else
        print_error "Storage directory: Not writable"
    fi

    if [[ -w "bootstrap/cache/" ]]; then
        print_success "Bootstrap cache: Writable"
    else
        print_error "Bootstrap cache: Not writable"
    fi

    # Check for common permission issues
    if [[ $(find . -name "*.log" -perm 644 | wc -l) -gt 0 ]]; then
        print_warning "Log files have world-readable permissions"
    fi
}

# Performance benchmark
performance_test() {
    print_section "PERFORMANCE" "Running performance tests..."

    # Test basic Laravel response
    print_info "Testing Laravel response time..."

    # Start Laravel if not running
    if ! curl -s --max-time 5 http://localhost:8000 > /dev/null 2>&1; then
        print_warning "Laravel not running locally, skipping web tests"
        return
    fi

    # Test API endpoint
    API_TIME=$(curl -s -o /dev/null -w "%{time_total}" http://localhost:8000/api/financial-highlights/dashboard 2>/dev/null || echo "0")
    API_TIME_MS=$(echo "$API_TIME * 1000" | bc 2>/dev/null || echo "0")

    if [[ $(echo "$API_TIME_MS > 0" | bc -l 2>/dev/null) -eq 1 ]]; then
        if [[ $(echo "$API_TIME_MS < 1000" | bc -l 2>/dev/null) -eq 1 ]]; then
            print_success "API Response: ${API_TIME_MS}ms"
        else
            print_warning "API Response: ${API_TIME_MS}ms (slow)"
        fi
    else
        print_error "API endpoint not accessible"
    fi

    # Memory usage test
    MEMORY_USAGE=$(php -r "
        \$start = memory_get_usage();
        // Simulate basic Laravel request
        require 'vendor/autoload.php';
        \$app = require_once 'bootstrap/app.php';
        \$kernel = \$app->make(Illuminate\Contracts\Http\Kernel::class);
        \$end = memory_get_usage();
        echo round((\$end - \$start) / 1024 / 1024, 2);
    " 2>/dev/null || echo "0")

    if [[ $(echo "$MEMORY_USAGE > 0" | bc -l 2>/dev/null) -eq 1 ]]; then
        if [[ $(echo "$MEMORY_USAGE < 50" | bc -l 2>/dev/null) -eq 1 ]]; then
            print_success "Memory usage: ${MEMORY_USAGE}MB"
        else
            print_warning "Memory usage: ${MEMORY_USAGE}MB (high)"
        fi
    fi
}

# Check web server
check_webserver() {
    print_section "WEBSERVER" "Checking web server configuration..."

    # Detect web server
    if pgrep -f nginx >/dev/null 2>&1; then
        print_success "Web server: Nginx detected"
        WEBSERVER="nginx"
    elif pgrep -f apache >/dev/null 2>&1; then
        print_success "Web server: Apache detected"
        WEBSERVER="apache"
    else
        print_warning "Web server: Not detected (nginx/apache)"
        return
    fi

    # Check if listening on port 80/443
    if netstat -tlnp 2>/dev/null | grep -q ":80 "; then
        print_success "Port 80: Listening"
    else
        print_warning "Port 80: Not listening"
    fi

    if netstat -tlnp 2>/dev/null | grep -q ":443 "; then
        print_success "Port 443: Listening (SSL)"
    else
        print_info "Port 443: Not listening (no SSL)"
    fi
}

# Generate recommendations
generate_recommendations() {
    print_section "RECOMMENDATIONS" "Optimization recommendations:"

    echo ""
    echo "🔧 IMMEDIATE FIXES:"
    echo "1. Enable OPcache in php.ini:"
    echo "   opcache.enable=1"
    echo "   opcache.memory_consumption=256"
    echo "   opcache.max_accelerated_files=7963"
    echo ""

    echo "2. Set production environment:"
    echo "   APP_ENV=production"
    echo "   APP_DEBUG=false"
    echo "   CACHE_STORE=redis"
    echo ""

    echo "3. Cache Laravel configuration:"
    echo "   php artisan config:cache"
    echo "   php artisan route:cache"
    echo "   php artisan view:cache"
    echo ""

    echo "4. Run database migration:"
    echo "   php artisan migrate --force"
    echo ""

    echo "🚀 ADVANCED OPTIMIZATIONS:"
    echo "1. Setup Redis server for caching"
    echo "2. Configure Nginx with proper caching headers"
    echo "3. Enable gzip compression"
    echo "4. Setup SSL certificate"
    echo "5. Configure CDN for static assets"
    echo "6. Setup queue workers for background jobs"
    echo ""

    echo "📊 MONITORING:"
    echo "1. Access Telescope: /telescope"
    echo "2. Monitor with: ./health-check.sh"
    echo "3. Check logs: tail -f storage/logs/laravel.log"
    echo ""

    print_info "See HOSTING_PERFORMANCE_GUIDE.md for detailed instructions"
}

# Quick fix function
quick_fix() {
    print_section "QUICK FIX" "Applying immediate optimizations..."

    # Clear all caches
    print_info "Clearing all caches..."
    php artisan cache:clear >/dev/null 2>&1
    php artisan config:clear >/dev/null 2>&1
    php artisan route:clear >/dev/null 2>&1
    php artisan view:clear >/dev/null 2>&1

    # Cache for production
    print_info "Caching for production..."
    php artisan config:cache >/dev/null 2>&1
    php artisan route:cache >/dev/null 2>&1
    php artisan view:cache >/dev/null 2>&1

    # Optimize composer
    if [[ -f "composer.json" ]]; then
        print_info "Optimizing Composer autoloader..."
        composer install --optimize-autoloader --no-dev >/dev/null 2>&1
    fi

    # Set permissions
    print_info "Setting correct permissions..."
    chmod -R 755 storage/ 2>/dev/null || true
    chmod -R 755 bootstrap/cache/ 2>/dev/null || true

    print_success "Quick fixes applied!"
    echo ""
    print_info "Restart your web server and PHP-FPM for changes to take effect"
}

# Main execution
main() {
    print_header

    check_project
    echo ""

    check_php
    echo ""

    check_environment
    echo ""

    check_database
    echo ""

    check_cache
    echo ""

    check_laravel_cache
    echo ""

    check_permissions
    echo ""

    performance_test
    echo ""

    check_webserver
    echo ""

    generate_recommendations

    echo ""
    read -p "Apply quick fixes now? (y/N): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        quick_fix
    fi

    echo ""
    print_success "Diagnostic complete! Check HOSTING_PERFORMANCE_GUIDE.md for detailed fixes."
}

# Run main function
main
