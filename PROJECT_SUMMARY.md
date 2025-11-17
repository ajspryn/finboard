# 🏦 FinBoard - Dashboard Bank

## 📝 Ringkasan Proyek

Aplikasi dashboard monitoring keuangan bank menggunakan **Laravel 11** dengan template **Vuexy**. Aplikasi ini menggunakan autentikasi PIN sederhana dan menampilkan data real-time untuk Funding, Lending, dan NPF (Non-Performing Financing).

---

## 🎯 Fitur Utama

### 1. **Autentikasi PIN**

- Login menggunakan PIN (tanpa username/password)
- PIN disimpan di file `.env` dengan key `DASHBOARD_PIN`
- Middleware `CheckPin` melindungi route dashboard
- Session-based authentication

### 2. **Dashboard Monitoring**

Menampilkan 3 modul keuangan utama:

#### a. **Funding (Dana Pihak Ketiga)**

- Total dana: Rp 25 Miliar
- Pertumbuhan: +5.2%
- Komposisi dana:
  - Tabungan: 40%
  - Deposito: 45%
  - Giro: 15%

#### b. **Lending (Pembiayaan)**

- Total pembiayaan: Rp 32 Miliar
- Rate Flat: 11.5%
- Rate Efektif: 19.9%
- Nasabah aktif: 245 orang

#### c. **NPF (Non-Performing Financing)**

- Total NPF: Rp 1.2 Miliar
- Rasio NPF: 3.75%
- Distribusi NPF:
  - Kurang Lancar: 40%
  - Diragukan: 30%
  - Macet: 30%

### 3. **Visualisasi Data**

- **Line Chart**: Tren bulanan Funding & Lending (6 bulan terakhir)
- **Donut Chart**: Distribusi NPF berdasarkan kategori
- Menggunakan ApexCharts (dari template Vuexy)

---

## 📁 Struktur File

```
finboard/
├── app/
│   └── Http/
│       ├── Controllers/
│       │   ├── AuthController.php          # Login & Logout PIN
│       │   ├── DashboardController.php     # Data dashboard dummy
│       │   └── Controller.php              # Base controller
│       └── Middleware/
│           └── CheckPin.php                # Middleware autentikasi PIN
│
├── bootstrap/
│   ├── app.php                             # Bootstrap Laravel application
│   └── cache/                              # Cache folder
│
├── config/
│   ├── app.php                             # Konfigurasi aplikasi
│   └── session.php                         # Konfigurasi session
│
├── public/
│   ├── index.php                           # Entry point
│   ├── .htaccess                           # Apache rewrite rules
│   └── template -> ../template             # Symlink ke template Vuexy
│
├── resources/
│   └── views/
│       ├── auth/
│       │   └── pin.blade.php              # Halaman login PIN
│       ├── layouts/
│       │   └── app.blade.php              # Layout utama (Vuexy)
│       └── dashboard.blade.php            # Halaman dashboard
│
├── routes/
│   ├── web.php                            # Web routes
│   └── console.php                        # Console routes
│
├── storage/
│   ├── framework/
│   │   ├── cache/
│   │   ├── sessions/                      # Session storage
│   │   └── views/                         # Compiled views
│   └── logs/                              # Application logs
│
├── template/                              # Template Vuexy
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── vendor/
│   │   └── img/
│   └── ...
│
├── .env                                   # Environment configuration
├── .gitignore                            # Git ignore rules
├── artisan                               # Laravel CLI
├── composer.json                         # PHP dependencies
├── package.json                          # Node dependencies
├── README.md                             # Dokumentasi utama
└── SETUP.md                              # Panduan setup detail
```

---

## 🔄 Flow Aplikasi

### 1. **Login Flow**

```
User mengakses /
  → AuthController@showLoginForm
  → Tampil pin.blade.php
  → User input PIN
  → POST /login
  → AuthController@login
  → Validasi PIN dengan .env
  → Jika benar: set session, redirect /dashboard
  → Jika salah: redirect back dengan error
```

### 2. **Dashboard Access Flow**

```
User akses /dashboard
  → Middleware CheckPin cek session
  → Jika tidak ada session: redirect /
  → Jika ada session: lanjut ke DashboardController@index
  → Controller siapkan data dummy
  → Return view dashboard.blade.php dengan data
```

### 3. **Logout Flow**

```
User klik Logout
  → GET /logout
  → AuthController@logout
  → Session::flush()
  → Redirect ke /
```

---

## 🗺️ Routing

| Route        | Method | Controller@Method            | Middleware | Deskripsi              |
| ------------ | ------ | ---------------------------- | ---------- | ---------------------- |
| `/`          | GET    | AuthController@showLoginForm | -          | Halaman login PIN      |
| `/login`     | POST   | AuthController@login         | -          | Proses login PIN       |
| `/dashboard` | GET    | DashboardController@index    | CheckPin   | Dashboard utama        |
| `/logout`    | GET    | AuthController@logout        | -          | Logout & hapus session |

---

## 🎨 Template & Assets

### Template Vuexy

- **Path**: `/template/`
- **Assets**: `/template/assets/`
- **Diakses via**: `/public/template/` (symbolic link)

### CSS Files Used:

- `/template/assets/vendor/css/rtl/core.css`
- `/template/assets/vendor/css/rtl/theme-default.css`
- `/template/assets/css/demo.css`
- `/template/assets/vendor/libs/apex-charts/apex-charts.css`

### JavaScript Files Used:

- `/template/assets/vendor/libs/jquery/jquery.js`
- `/template/assets/vendor/js/bootstrap.js`
- `/template/assets/vendor/libs/apex-charts/apexcharts.js`
- `/template/assets/js/main.js`

---

## 💾 Data Management

### Current: Dummy Data

Data saat ini disimpan sebagai array di `DashboardController`:

```php
$funding = ['total' => 25000000000, 'growth' => 5.2, ...];
$lending = ['total' => 32000000000, 'rate_flat' => 11.5, ...];
$npf = ['total' => 1200000000, 'ratio' => 3.75];
```

### Future: Database Integration

Untuk koneksi database real:

1. **Setup database di `.env`**:

   ```env
   DB_CONNECTION=mysql
   DB_DATABASE=finboard
   ```

2. **Buat migrations**:

   ```bash
   php artisan make:migration create_funding_table
   php artisan make:migration create_lending_table
   php artisan make:migration create_npf_table
   ```

3. **Buat models**:

   ```bash
   php artisan make:model Funding
   php artisan make:model Lending
   php artisan make:model NPF
   ```

4. **Update DashboardController** untuk fetch dari database

---

## 🔐 Security

### PIN Authentication

- PIN disimpan di `.env`: `DASHBOARD_PIN=123456`
- **PENTING**: Ubah PIN default sebelum production!
- Session timeout: 120 menit (konfigurasi di `.env`)

### Session Management

- Driver: File-based (default)
- Location: `storage/framework/sessions/`
- Lifetime: 120 minutes

### CSRF Protection

- Laravel CSRF token otomatis di semua form POST
- Token tersimpan di session

### Recommended Security Enhancements:

1. Hash PIN menggunakan bcrypt
2. Implement rate limiting untuk login
3. Add 2FA (Two-Factor Authentication)
4. Log semua login attempts
5. Setup HTTPS di production

---

## 🚀 Deployment Checklist

### Before Deploy:

- [ ] Ubah `DASHBOARD_PIN` di `.env`
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate `APP_KEY`: `php artisan key:generate`
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run cache commands:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
- [ ] Set proper file permissions (755/644)
- [ ] Setup SSL certificate (HTTPS)
- [ ] Configure web server (Nginx/Apache)

### Monitoring:

- Setup log rotation
- Monitor `storage/logs/laravel.log`
- Setup error tracking (Sentry, Bugsnag, etc.)

---

## 🎯 Roadmap Pengembangan

### Phase 1: ✅ Completed

- [x] Setup Laravel 11
- [x] Integrasi template Vuexy
- [x] Autentikasi PIN
- [x] Dashboard dengan 3 modul (Funding, Lending, NPF)
- [x] Grafik interaktif (Line & Donut chart)

### Phase 2: 🔄 Next Steps

- [ ] Modul Profit
- [ ] Modul Aset
- [ ] Modul Rasio Keuangan
- [ ] Database integration
- [ ] Data real-time dari API
- [ ] Export data ke Excel/PDF
- [ ] Filter by date range
- [ ] User management (multi-user)

### Phase 3: 🚀 Advanced Features

- [ ] Role-based access control
- [ ] Audit log
- [ ] Email notifications
- [ ] Scheduled reports
- [ ] Mobile responsive improvements
- [ ] API untuk mobile app

---

## 🛠️ Tech Stack

- **Backend**: Laravel 11 (PHP 8.1+)
- **Frontend**: Blade Templates
- **UI Framework**: Vuexy Template (Bootstrap 5)
- **Charts**: ApexCharts
- **Icons**: Tabler Icons
- **Authentication**: Session-based (PIN)
- **Database**: MySQL/MariaDB (optional)

---

## 📞 Maintenance

### Regular Tasks:

- Backup `.env` file
- Clear cache: `php artisan optimize:clear`
- Update dependencies: `composer update`
- Check logs: `tail -f storage/logs/laravel.log`
- Monitor disk space (session files)

### Version Updates:

```bash
# Update composer dependencies
composer update

# Clear all cache
php artisan optimize:clear

# Re-cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📚 Resources

- **Laravel Documentation**: https://laravel.com/docs/11.x
- **Vuexy Documentation**: Lihat folder `template/documentation/`
- **ApexCharts**: https://apexcharts.com/docs/
- **Bootstrap 5**: https://getbootstrap.com/docs/5.3/

---

## ✨ Credits

- Framework: Laravel
- Template: Vuexy Admin Template
- Charts Library: ApexCharts
- Icons: Tabler Icons

---

**Version**: 1.0.0  
**Last Updated**: November 2025  
**Status**: Production Ready (dengan catatan: ubah PIN default!)
