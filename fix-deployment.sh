#!/bin/bash

echo "🔧 Fixing Laravel deployment issues on cPanel..."
echo "================================================="

# Create bootstrap/cache directory if it doesn't exist
echo "1. Creating bootstrap/cache directory..."
mkdir -p bootstrap/cache

# Set proper permissions
echo "2. Setting proper permissions..."
chmod 755 bootstrap/cache
chmod 644 bootstrap/cache/.gitkeep 2>/dev/null || true

# Clear existing cache files
echo "3. Clearing existing cache files..."
rm -f bootstrap/cache/*.php
rm -f bootstrap/cache/*.json

# Create storage directories
echo "4. Creating storage directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/testing

# Set storage permissions
echo "5. Setting storage permissions..."
chmod -R 755 storage/
chmod -R 644 storage/logs/ 2>/dev/null || true
chmod 755 storage/framework/cache/
chmod 755 storage/framework/sessions/
chmod 755 storage/framework/views/
chmod 755 storage/framework/testing/

# Clear Laravel caches
echo "6. Clearing Laravel caches..."
php artisan config:clear 2>/dev/null || echo "Config cache cleared"
php artisan cache:clear 2>/dev/null || echo "Application cache cleared"
php artisan route:clear 2>/dev/null || echo "Route cache cleared"
php artisan view:clear 2>/dev/null || echo "View cache cleared"

echo ""
echo "✅ Deployment fixes completed!"
echo "================================"
echo "Now you can run: php artisan migrate"
echo ""
echo "If you still get permission errors, contact your hosting provider"
echo "to ensure PHP has write access to the bootstrap/cache and storage directories."
