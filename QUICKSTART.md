# 🚀 Quick Start Guide - FinBoard

## Instalasi Cepat (5 Menit)

### ✅ Prerequisites

- PHP >= 8.1
- Composer (opsional untuk testing)

### 📦 Step 1: Persiapan

```bash
cd /Users/ajspryn/Project/finboard
```

### 🔑 Step 2: Set PIN Anda

Edit file `.env` dan ubah PIN:

```bash
# Buka file .env
nano .env

# Atau gunakan editor favorit Anda
# Cari dan ubah baris ini:
DASHBOARD_PIN=123456

# Ganti dengan PIN Anda, misal:
DASHBOARD_PIN=654321
```

### 🔐 Step 3: Set Permissions

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod +x artisan
```

### 🌐 Step 4: Jalankan Server

**Pilihan A: Tanpa Composer (Simple PHP Server)**

```bash
php -S localhost:8000 -t public
```

**Pilihan B: Dengan Laravel (Recommended)**

```bash
# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Jalankan server
php artisan serve
```

### 🎉 Step 5: Akses Dashboard

Buka browser dan akses:

```
http://localhost:8000
```

**Login dengan PIN:**

- PIN default: `123456` (atau yang sudah Anda ubah)

---

## 📱 Demo Login

1. **Buka halaman**: `http://localhost:8000`
2. **Masukkan PIN**: `123456`
3. **Klik Login**
4. **Dashboard terbuka!** ✨

---

## 🎯 Apa yang Akan Anda Lihat?

### 1. Halaman Login

- Form input PIN sederhana
- Desain modern dengan template Vuexy
- Error message jika PIN salah

### 2. Dashboard Utama

Setelah login berhasil, Anda akan melihat:

#### 📊 Kartu Statistik

- **Funding Card**: Total Rp 25M, pertumbuhan +5.2%
- **Lending Card**: Total Rp 32M, rate 11.5%, 245 nasabah
- **NPF Card**: Total Rp 1.2M, rasio 3.75%

#### 📈 Grafik Interaktif

- **Line Chart**: Tren Funding & Lending 6 bulan terakhir
- **Donut Chart**: Distribusi NPF (Kurang Lancar, Diragukan, Macet)

#### 🔝 Sidebar Menu

- Dashboard (aktif)
- Funding
- Lending
- NPF
- Profit (coming soon)
- Aset (coming soon)
- Rasio Keuangan (coming soon)

### 3. Logout

Klik avatar di pojok kanan atas → Logout

---

## 🛠️ Troubleshooting

### ❌ Error: "Session store not set"

**Solusi:**

```bash
php artisan config:clear
```

### ❌ Error: Template assets tidak muncul (CSS/JS hilang)

**Solusi:**

```bash
cd public
ln -sf ../template template
ls -la  # Pastikan symlink 'template' ada
```

### ❌ Error: "Permission denied" saat akses storage

**Solusi:**

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### ❌ Error: "419 Page Expired" saat login

**Solusi:**

1. Clear browser cookies
2. Hard refresh (Cmd+Shift+R di Mac, Ctrl+Shift+R di Windows)
3. Atau clear config:

```bash
php artisan config:clear
```

### ❌ Server tidak jalan

**Cek apakah port 8000 sudah dipakai:**

```bash
lsof -i :8000
```

**Gunakan port lain:**

```bash
php artisan serve --port=8080
```

---

## 🔧 Kustomisasi Cepat

### Mengubah PIN

File: `.env`

```env
DASHBOARD_PIN=PIN_BARU_ANDA
```

### Mengubah Data Dummy

File: `app/Http/Controllers/DashboardController.php`

```php
$funding = [
    'total' => 30000000000,  // Ubah nilai ini
    'growth' => 8.5,         // Ubah pertumbuhan
    'composition' => [
        'Tabungan' => 50,    // Ubah komposisi
        'Deposito' => 30,
        'Giro' => 20
    ]
];
```

Refresh browser untuk melihat perubahan.

### Mengubah Warna Theme

File: `template/assets/vendor/css/rtl/theme-default.css`

Atau gunakan customizer yang sudah tersedia di template Vuexy.

---

## 📸 Screenshot Fitur

### Login Page

- Form input PIN
- Logo FinBoard
- Welcome message
- Error handling

### Dashboard

- **Header**: Judul "Dashboard Bank"
- **Cards Section**: 3 kartu besar (Funding, Lending, NPF)
- **Charts Section**:
  - Line chart (kiri, 8 kolom)
  - Donut chart (kanan, 4 kolom)
- **Sidebar**: Menu navigasi
- **Navbar**: User profile & logout

---

## 🎓 Next Steps

Setelah berhasil menjalankan aplikasi:

1. **Explore Dashboard**

   - Lihat data di setiap kartu
   - Hover pada grafik untuk detail
   - Klik menu sidebar (belum ada konten)

2. **Customize Data**

   - Edit `DashboardController.php`
   - Ubah nilai dummy sesuai kebutuhan

3. **Add New Module**

   - Buat controller baru
   - Tambahkan route
   - Buat view baru
   - Update sidebar menu

4. **Connect to Database**

   - Setup MySQL/MariaDB
   - Buat migrations
   - Update controller untuk fetch dari DB

5. **Deploy to Production**
   - Follow panduan di `SETUP.md`
   - Set environment ke `production`
   - Enable caching
   - Setup HTTPS

---

## 📚 Dokumentasi Lengkap

- **README.md**: Overview & fitur aplikasi
- **SETUP.md**: Panduan setup detail
- **TECHNICAL.md**: Dokumentasi teknis & code explanation
- **PROJECT_SUMMARY.md**: Ringkasan lengkap project

---

## 💡 Tips

### Untuk Development

```bash
# Watch file changes (jika pakai npm)
npm run watch

# Clear semua cache
php artisan optimize:clear

# View logs
tail -f storage/logs/laravel.log
```

### Untuk Testing

```bash
# Test di berbagai device
php artisan serve --host=0.0.0.0 --port=8000
# Akses dari device lain: http://IP_KOMPUTER_ANDA:8000
```

### Untuk Production

```bash
# Optimize semua
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🎉 Selamat!

Anda telah berhasil setup **FinBoard Dashboard Bank**!

**Default Login:**

- URL: `http://localhost:8000`
- PIN: `123456`

Jangan lupa ubah PIN di `.env` untuk keamanan! 🔐

---

## 📞 Need Help?

Cek file dokumentasi:

- Ada error? → Lihat bagian Troubleshooting di atas
- Mau tambah fitur? → Baca `TECHNICAL.md`
- Setup production? → Baca `SETUP.md`
- Overview lengkap? → Baca `PROJECT_SUMMARY.md`

**Happy coding! 🚀**
