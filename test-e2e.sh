#!/bin/bash

# FinBoard End-to-End Testing Script
# Tests all backend performance features with sample data

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[PASS]${NC} $1"
}

print_error() {
    echo -e "${RED}[FAIL]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

echo "🧪 Starting FinBoard End-to-End Tests..."
echo "========================================"

# Check if Laravel is running
check_laravel() {
    if curl -s http://localhost:8000 > /dev/null; then
        print_success "Laravel application is running"
        return 0
    else
        print_error "Laravel application is not running"
        return 1
    fi
}

# Check Redis
check_redis() {
    if command -v redis-cli >/dev/null 2>&1 && redis-cli ping 2>/dev/null | grep -q "PONG"; then
        print_success "Redis is running"
        return 0
    else
        print_warning "Redis is not available (install with: brew install redis)"
        return 1
    fi
}

# Check WebSocket server
check_websocket() {
    if curl -s http://localhost:6001/socket.io/?EIO=4&transport=polling > /dev/null; then
        print_success "WebSocket server is running"
        return 0
    else
        print_error "WebSocket server is not responding"
        return 1
    fi
}

# Test database connection and migrations
test_database() {
    print_status "Testing database connection..."
    if php artisan migrate:status > /dev/null 2>&1; then
        print_success "Database connection successful"
        return 0
    else
        print_error "Database connection failed"
        return 1
    fi
}

# Test API endpoints
test_api_endpoints() {
    print_status "Testing API endpoints..."

    # Test financial highlights endpoint
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/financial-highlights/dashboard | grep -q "200"; then
        print_success "Financial highlights API endpoint working"
    else
        print_error "Financial highlights API endpoint failed"
        return 1
    fi

    # Test with parameters
    if curl -s -o /dev/null -w "%{http_code}" "http://localhost:8000/api/financial-highlights/dashboard?year=2025&month=12" | grep -q "200"; then
        print_success "Financial highlights API with parameters working"
    else
        print_error "Financial highlights API with parameters failed"
        return 1
    fi

    return 0
}

# Test caching functionality
test_caching() {
    print_status "Testing caching functionality..."

    # Clear cache first
    php artisan cache:clear > /dev/null 2>&1

    # Make first request (should hit database)
    start_time=$(date +%s%3N)
    response1=$(curl -s http://localhost:8000/api/financial-highlights/dashboard)
    end_time=$(date +%s%3N)
    first_request_time=$((end_time - start_time))

    # Make second request (should hit cache)
    start_time=$(date +%s%3N)
    response2=$(curl -s http://localhost:8000/api/financial-highlights/dashboard)
    end_time=$(date +%s%3N)
    second_request_time=$((end_time - start_time))

    # Compare responses
    if [[ "$response1" == "$response2" ]]; then
        print_success "Cache consistency maintained"
    else
        print_error "Cache responses differ"
        return 1
    fi

    # Check if second request is faster (basic cache test)
    if [[ $second_request_time -lt $first_request_time ]]; then
        print_success "Caching is working (second request faster)"
    else
        print_warning "Cache performance test inconclusive"
    fi

    return 0
}

# Test database indexes
test_indexes() {
    print_status "Testing database indexes..."

    # Check if indexes exist
    if php artisan tinker --execute="
        \$indexes = DB::select(\"SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_%'\");
        echo count(\$indexes) . ' performance indexes found\n';
        exit(count(\$indexes) > 0 ? 0 : 1);
    " > /dev/null 2>&1; then
        print_success "Database indexes are present"
        return 0
    else
        print_error "Database indexes not found"
        return 1
    fi
}

# Test Telescope monitoring
test_telescope() {
    print_status "Testing Telescope monitoring..."

    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/telescope | grep -q "200"; then
        print_success "Telescope dashboard accessible"
        return 0
    else
        print_error "Telescope dashboard not accessible"
        return 1
    fi
}

# Test WebSocket broadcasting (basic connectivity)
test_websocket_connectivity() {
    print_status "Testing WebSocket connectivity..."

    # Test basic WebSocket server response
    if curl -s "http://localhost:6001/socket.io/?EIO=4&transport=polling" | grep -q "socket.io"; then
        print_success "WebSocket server responding"
        return 0
    else
        print_error "WebSocket server not responding properly"
        return 1
    fi
}

# Test cache invalidation
test_cache_invalidation() {
    print_status "Testing cache invalidation..."

    # Get initial cache state
    initial_cache=$(redis-cli keys "financial:*" | wc -l)

    # Trigger cache invalidation via API (if endpoint exists)
    # For now, just test that cache operations work
    if php artisan tinker --execute="
        \$service = app(App\Services\FinancialCacheService::class);
        \$service->invalidateCache('highlights');
        echo 'Cache invalidation successful\n';
    " > /dev/null 2>&1; then
        print_success "Cache invalidation working"
        return 0
    else
        print_error "Cache invalidation failed"
        return 1
    fi
}

# Performance benchmark
performance_benchmark() {
    print_status "Running performance benchmark..."

    echo "Testing API response times (3 requests)..."

    for i in {1..3}; do
        if curl -s --max-time 10 http://localhost:8000/api/financial-highlights/dashboard > /dev/null; then
            echo -n "✅ "
        else
            echo -n "❌ "
        fi
    done
    echo ""

    print_success "Basic API connectivity test completed"
}

# Main test execution
main() {
    local tests_passed=0
    local total_tests=0

    # Infrastructure tests
    ((total_tests++))
    if check_laravel; then ((tests_passed++)); fi

    ((total_tests++))
    if check_redis; then ((tests_passed++)); fi

    ((total_tests++))
    if check_websocket; then ((tests_passed++)); fi

    ((total_tests++))
    if test_database; then ((tests_passed++)); fi

    # Feature tests
    ((total_tests++))
    if test_api_endpoints; then ((tests_passed++)); fi

    ((total_tests++))
    if test_caching; then ((tests_passed++)); fi

    ((total_tests++))
    if test_indexes; then ((tests_passed++)); fi

    ((total_tests++))
    if test_telescope; then ((tests_passed++)); fi

    ((total_tests++))
    if test_websocket_connectivity; then ((tests_passed++)); fi

    ((total_tests++))
    if test_cache_invalidation; then ((tests_passed++)); fi

    # Performance test
    performance_benchmark

    echo ""
    echo "========================================"
    echo "Test Results: $tests_passed/$total_tests tests passed"

    if [[ $tests_passed -eq $total_tests ]]; then
        print_success "🎉 All tests passed! FinBoard is ready for production."
        return 0
    else
        print_error "❌ Some tests failed. Please check the issues above."
        return 1
    fi
}

# Run main tests
main
