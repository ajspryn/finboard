# Setup Guide - FinBoard Dashboard Bank

## 🎯 Quick Start (Tanpa Laravel Full Install)

Jika Anda ingin langsung testing tanpa install Laravel penuh:

### 1. Pastikan PHP terinstall

```bash
php -v
# Harus PHP >= 8.1
```

### 2. Install Composer Dependencies (Opsional untuk Testing)

Untuk sementara, aplikasi bisa dijalankan tanpa `composer install` jika Anda hanya ingin melihat struktur kode.

### 3. Setup Environment

File `.env` sudah tersedia. Ubah PIN jika diperlukan:

```bash
nano .env
# Ubah: DASHBOARD_PIN=123456 menjadi PIN yang Anda inginkan
```

### 4. Generate Application Key (Jika menggunakan Laravel penuh)

```bash
php artisan key:generate
```

### 5. Set Permission untuk Storage

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### 6. Jalankan Development Server

```bash
php artisan serve
```

Atau jika belum install Laravel:

```bash
php -S localhost:8000 -t public
```

Akses: `http://localhost:8000`

---

## 📦 Full Laravel Installation

Jika ingin menggunakan Laravel lengkap dengan database dan fitur penuh:

### 1. Install Laravel via Composer

```bash
cd /Users/ajspryn/Project/finboard
composer install
```

### 2. Setup Database (Opsional - saat ini belum digunakan)

Edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finboard
DB_USERNAME=root
DB_PASSWORD=your_password
```

Buat database:

```bash
mysql -u root -p
CREATE DATABASE finboard;
exit;
```

Jalankan migrasi (jika nanti ada):

```bash
php artisan migrate
```

### 3. Generate Application Key

```bash
php artisan key:generate
```

### 4. Clear Cache (jika diperlukan)

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### 5. Run Development Server

```bash
php artisan serve
# Atau dengan custom host/port
php artisan serve --host=0.0.0.0 --port=8080
```

---

## 🔧 Troubleshooting

### Error: "Target class [App\Http\Controllers\AuthController] does not exist"

**Solusi:**

```bash
composer dump-autoload
php artisan clear-compiled
php artisan optimize
```

### Error: Template assets tidak muncul (404 pada CSS/JS)

**Solusi:**

```bash
cd public
ls -la template  # Cek apakah symlink sudah ada

# Jika belum, buat symlink
ln -sf ../template template
```

### Error: "Session store not set on request"

**Solusi:**

```bash
# Pastikan SESSION_DRIVER di .env
SESSION_DRIVER=file

# Clear cache
php artisan config:clear
php artisan cache:clear
```

### Error: "The stream or file could not be opened"

**Solusi:**

```bash
# Set permission storage
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Atau untuk development (lebih permisif)
chmod -R 777 storage
chmod -R 777 bootstrap/cache
```

### Error: "419 | Page Expired" saat login

**Solusi:**

1. Clear browser cookies
2. Pastikan session berjalan:
   ```bash
   php artisan config:clear
   ```
3. Cek file `.env`:
   ```env
   SESSION_DRIVER=file
   SESSION_LIFETIME=120
   ```

---

## 🚀 Deploy to Production

### 1. Set Environment ke Production

Edit `.env`:

```env
APP_ENV=production
APP_DEBUG=false
```

### 2. Optimize untuk Production

```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Setup Web Server (Nginx/Apache)

**Nginx:**

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/finboard/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Apache (.htaccess sudah ada di public folder):**

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/finboard/public

    <Directory /path/to/finboard/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. Set Permission (Production)

```bash
chown -R www-data:www-data /path/to/finboard
chmod -R 755 /path/to/finboard
chmod -R 775 /path/to/finboard/storage
chmod -R 775 /path/to/finboard/bootstrap/cache
```

### 5. Setup SSL (Recommended)

```bash
# Menggunakan Let's Encrypt
sudo certbot --nginx -d yourdomain.com
```

---

## 🔐 Security Best Practices

1. **Ubah PIN Default**

   ```env
   DASHBOARD_PIN=your_secure_pin_here
   ```

2. **Generate Strong APP_KEY**

   ```bash
   php artisan key:generate
   ```

3. **Disable Debug di Production**

   ```env
   APP_DEBUG=false
   ```

4. **Set Proper File Permissions**

   - Production: 755 untuk folders, 644 untuk files
   - Storage & Cache: 775

5. **Gunakan HTTPS di Production**

6. **Backup Regular**
   - Backup `.env` file (dengan aman)
   - Backup database (jika digunakan)
   - Backup uploaded files

---

## 📊 Monitoring

### Check Application Status

```bash
php artisan about
```

### View Logs

```bash
tail -f storage/logs/laravel.log
```

### Clear All Cache

```bash
php artisan optimize:clear
```

---

## 🆘 Support

Jika mengalami masalah:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server logs (Nginx/Apache)
3. Run `php artisan about` untuk info sistem
4. Pastikan requirements terpenuhi (PHP >= 8.1)

---

## ✅ Checklist Setup

- [ ] PHP >= 8.1 terinstall
- [ ] Composer terinstall (jika pakai Laravel penuh)
- [ ] Clone/extract project ke folder
- [ ] Copy `.env.example` ke `.env` (sudah ada)
- [ ] Set `DASHBOARD_PIN` di `.env`
- [ ] Run `composer install` (opsional)
- [ ] Run `php artisan key:generate`
- [ ] Set permission storage: `chmod -R 775 storage bootstrap/cache`
- [ ] Create symlink template: `ln -s ../template public/template`
- [ ] Run `php artisan serve`
- [ ] Akses `http://localhost:8000`
- [ ] Login dengan PIN yang sudah diset

---

**Selamat menggunakan FinBoard Dashboard Bank! 🎉**
