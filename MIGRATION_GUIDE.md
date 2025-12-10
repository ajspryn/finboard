# Panduan Migrasi Database: SQLite → MySQL

# FinBoard Application

## 📋 Daftar Langkah Migrasi

### 1. ✅ Backup Database SQLite (SUDAH DILAKUKAN)

```bash
cp .env .env.backup  # ✅ Backup konfigurasi
```

### 2. ✅ Update Konfigurasi Database (SUDAH DILAKUKAN)

File `.env` sudah diupdate:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finboard_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. 🔄 Buat Database MySQL

Jalankan perintah berikut di MySQL:

```sql
CREATE DATABASE finboard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. 🔄 Jalankan Migrasi Laravel

```bash
php artisan migrate
```

### 5. 🔄 Jalankan Seeder (jika ada data awal)

```bash
php artisan db:seed
```

### 6. 🔄 Test Koneksi Database

```bash
php artisan tinker
```

Kemudian jalankan:

```php
DB::connection()->getPdo();
echo "Koneksi MySQL berhasil!";
```

## ⚠️ PENTING: Migrasi Data

Jika ada data penting di database SQLite yang perlu dipindahkan:

### A. Export data dari SQLite:

```bash
sqlite3 database/database.sqlite .dump > backup_sqlite.sql
```

### B. Convert SQLite dump ke MySQL format:

```bash
# Gunakan tool seperti sqlite3-to-mysql atau manual conversion
# Atau gunakan Laravel seeder untuk import data

php artisan tinker
```

```php
// Di dalam tinker, import data dari SQLite ke MySQL
$users = DB::connection('sqlite')->table('users')->get();
foreach($users as $user) {
    DB::connection('mysql')->table('users')->insert((array)$user);
}
// Lakukan untuk semua tabel yang diperlukan
```

## 🔧 Troubleshooting

### Error: "PDOException: could not find driver"

```bash
# Install PHP MySQL extension
sudo apt-get install php8.3-mysql  # Ubuntu/Debian
# atau
brew install php@8.3              # macOS
```

### Error: "Access denied for user"

- Pastikan username/password MySQL benar
- Pastikan user MySQL memiliki akses ke database `finboard_db`

### Error: "Database does not exist"

- Pastikan database `finboard_db` sudah dibuat
- Periksa nama database di `.env`

## 📊 Keuntungan Migrasi ke MySQL

1. **Performance**: Lebih baik untuk aplikasi production
2. **Concurrency**: Mendukung multiple connections
3. **Features**: Full-text search, stored procedures, triggers
4. **Scalability**: Lebih mudah di-scale
5. **Backup**: Tools backup yang lebih mature
6. **Compatibility**: Standard SQL yang lebih luas

## 🔄 Rollback (jika diperlukan)

Jika ada masalah, kembali ke SQLite:

```bash
cp .env.backup .env
php artisan config:clear
php artisan cache:clear
```

## ✅ Verifikasi Migrasi

Setelah migrasi berhasil:

```bash
php artisan migrate:status  # Semua migration harus "Ran"
php artisan tinker         # Test query database
```

## 📞 Support

Jika mengalami kesulitan:

1. Periksa log Laravel: `storage/logs/laravel.log`
2. Test koneksi: `php artisan tinker` → `DB::connection()->getPdo()`
3. Periksa konfigurasi MySQL server
