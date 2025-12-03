# 🚀 FinBoard Hosting Performance Optimization Guide

## Masalah: Aplikasi Lambat di Hosting Environment

Panduan lengkap mengatasi masalah performa FinBoard di hosting/production environment.

## 🔍 Diagnosa Masalah Performa

### 1. Cek Environment Configuration

**File `.env` Production:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting
BROADCAST_CONNECTION=redis

# Monitoring
TELESCOPE_ENABLED=true
```

### 2. Cek PHP Configuration

**File `php.ini` (atau `.user.ini` di hosting):**

```ini
; Performance Settings
memory_limit=256M
max_execution_time=300
max_input_time=300

; OPcache (CRITICAL untuk performa)
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.max_accelerated_files=7963
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=0
opcache.enable_file_override=1

; Realpath Cache
realpath_cache_size=4096K
realpath_cache_ttl=600
```

### 3. Cek Web Server Configuration

**Nginx Configuration (`/etc/nginx/sites-available/finboard`):**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/finboard/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    # PHP FastCGI
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # FastCGI optimizations
        fastcgi_buffering on;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
        fastcgi_read_timeout 300;
    }

    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Laravel specific
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
}
```

**Apache Configuration (`.htaccess`):**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>
```

## 🛠️ Troubleshooting Steps

### Step 1: Cek Database Performance

```bash
# Cek apakah indexes sudah ada
php artisan tinker
>>> DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name LIKE 'idx_%'");
>>> exit;

# Jika menggunakan MySQL
mysql -u username -p database_name -e "SHOW INDEX FROM pembiayaans;"

# Jalankan migration jika belum
php artisan migrate --force
```

### Step 2: Cek Redis Connection

```bash
# Test Redis connection
redis-cli ping

# Cek Redis memory usage
redis-cli info memory

# Clear cache jika bermasalah
php artisan cache:clear
redis-cli FLUSHALL
```

### Step 3: Cek Laravel Performance

```bash
# Clear semua cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Cek Telescope untuk bottlenecks
# Akses: https://your-domain.com/telescope
```

### Step 4: Monitor Resource Usage

```bash
# Cek memory usage
php -r "echo 'Memory: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB\n';"

# Cek CPU usage
top -p $(pgrep -f "php")

# Cek disk I/O
iostat -x 1 5

# Cek network
netstat -tlnp | grep :80
```

## 🚀 Advanced Optimizations

### 1. Database Optimizations

**MySQL Configuration (`my.cnf`):**

```ini
[mysqld]
innodb_buffer_pool_size=256M
innodb_log_file_size=64M
query_cache_size=64M
query_cache_type=1
max_connections=100

# For FinBoard specific
innodb_flush_log_at_trx_commit=2
innodb_flush_method=O_DIRECT
```

**Query Optimizations:**

```php
// Di FinancialHighlightController
public function dashboard(Request $request)
{
    return Cache::remember('financial-dashboard', 3600, function () use ($request) {
        // Optimized queries dengan eager loading
        $highlights = FinancialHighlight::with(['pembiayaans', 'tabungans', 'depositos'])
            ->where('period_year', $request->year ?? date('Y'))
            ->where('period_month', $request->month ?? date('m'))
            ->first();

        return response()->json($highlights);
    });
}
```

### 2. Redis Cluster (untuk high traffic)

**Redis Configuration:**

```redis.conf
# Memory management
maxmemory 256mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Performance
tcp-keepalive 300
timeout 300
```

### 3. CDN untuk Static Assets

**Cloudflare Configuration:**

- Enable caching untuk `/css/*`, `/js/*`, `/images/*`
- Set browser cache TTL: 1 year
- Enable gzip compression
- Enable HTTP/2

### 4. Queue System untuk Heavy Tasks

```bash
# Setup queue worker
php artisan queue:work --sleep=3 --tries=3 --max-jobs=1000 --memory=128 --timeout=90

# Supervisor configuration (/etc/supervisor/conf.d/finboard-queue.conf)
[program:finboard-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/finboard/artisan queue:work --sleep=3 --tries=3 --max-jobs=1000
directory=/var/www/finboard
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/finboard/storage/logs/queue.log
```

## 📊 Performance Monitoring

### 1. Real-time Monitoring dengan Telescope

```php
// Di routes/web.php
if (config('telescope.enabled')) {
    Route::get('/telescope', function () {
        return redirect('/telescope/requests');
    });
}
```

### 2. Custom Performance Metrics

**File `app/Http/Middleware/PerformanceMonitor.php`:**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class PerformanceMonitor
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $end = microtime(true);
        $duration = ($end - $start) * 1000; // Convert to milliseconds

        if ($duration > 1000) { // Log slow requests (>1s)
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'duration' => round($duration, 2) . 'ms',
                'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
            ]);
        }

        return $response;
    }
}
```

### 3. Automated Health Checks

**File `routes/api.php`:**

```php
Route::get('/health', function () {
    $health = [
        'status' => 'ok',
        'timestamp' => now(),
        'checks' => [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'cache' => $this->checkCache(),
        ]
    ];

    $statusCode = collect($health['checks'])->contains('status', 'error') ? 500 : 200;

    return response()->json($health, $statusCode);
});

private function checkDatabase()
{
    try {
        DB::connection()->getPdo();
        return ['status' => 'ok', 'response_time' => 'N/A'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

private function checkRedis()
{
    try {
        Redis::ping();
        return ['status' => 'ok'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

private function checkCache()
{
    try {
        Cache::store('redis')->put('health_check', 'ok', 10);
        $value = Cache::store('redis')->get('health_check');
        return $value === 'ok' ? ['status' => 'ok'] : ['status' => 'error'];
    } catch (\Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
```

## 🔧 Quick Fix Commands

### Untuk Hosting Shared (cPanel/Plesk)

```bash
# 1. Clear semua cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Optimize autoloader
composer install --optimize-autoloader --no-dev

# 3. Cache untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Jalankan migration
php artisan migrate --force

# 5. Set permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### Untuk VPS/Cloud Server

```bash
# 1. Install dependencies
sudo apt update
sudo apt install -y redis-server nginx php8.1-fpm php8.1-redis php8.1-mysql

# 2. Configure services
sudo systemctl enable redis-server
sudo systemctl enable nginx
sudo systemctl enable php8.1-fpm

# 3. Setup SSL (Let's Encrypt)
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com

# 4. Monitor services
sudo systemctl status redis-server
sudo systemctl status nginx
sudo systemctl status php8.1-fpm
```

## 📈 Performance Benchmarks

### Target Performance:

| Metric        | Target | Acceptable | Poor   |
| ------------- | ------ | ---------- | ------ |
| First Load    | <2s    | <5s        | >5s    |
| Cached Load   | <500ms | <1s        | >1s    |
| API Response  | <200ms | <500ms     | >500ms |
| Memory Usage  | <128MB | <256MB     | >256MB |
| DB Query Time | <50ms  | <100ms     | >100ms |

### Monitoring Commands:

```bash
# Real-time monitoring
watch -n 1 "ps aux | grep php | head -5"

# Memory usage
php -r "echo 'Peak Memory: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB\n';"

# Cache hit rate
redis-cli info stats | grep keyspace

# Database connections
mysql -e "SHOW PROCESSLIST;" | wc -l
```

## 🚨 Emergency Fixes

### Jika aplikasi completely down:

```bash
# 1. Check error logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.1-fpm.log

# 2. Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl restart redis-server

# 3. Clear all caches
php artisan cache:clear
redis-cli FLUSHALL
```

### Jika memory exhausted:

```bash
# Increase PHP memory limit
php -d memory_limit=512M artisan cache:clear

# Check for memory leaks
php artisan tinker
>>> memory_get_peak_usage(true) / 1024 / 1024
```

---

## 📞 Support Checklist

Sebelum menghubungi support, pastikan sudah cek:

- [ ] Environment variables sudah benar
- [ ] Database connection OK
- [ ] Redis connection OK
- [ ] File permissions benar
- [ ] PHP OPcache enabled
- [ ] Web server configuration OK
- [ ] SSL certificate valid
- [ ] CDN configured (jika ada)
- [ ] Queue workers running
- [ ] Logs tidak ada error

**Next Steps:**

1. Jalankan `./health-check.sh` untuk diagnosis awal
2. Cek Telescope dashboard untuk bottlenecks
3. Monitor dengan commands di atas
4. Implement optimasi bertahap

---

**Status**: ✅ Production optimization guide ready
**Next**: Test di hosting environment dan monitor performa
