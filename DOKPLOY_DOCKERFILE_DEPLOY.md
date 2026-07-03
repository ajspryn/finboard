# Dokploy Deploy via Dockerfile (Opsi A)

Tujuan: pindah dari Nixpacks ke Dockerfile agar runtime sesuai kebutuhan aplikasi upload CSV dan queue.

## 1) Ubah tipe deploy di Dokploy

Gunakan build dari Dockerfile, bukan Nixpacks.

- Source Type: Git repository
- Build Type: Dockerfile
- Dockerfile Path: ./Dockerfile
- Port aplikasi: 80

Catatan: aplikasi ini sudah membawa Nginx + PHP-FPM + queue worker melalui Supervisor.

## 2) Environment variables wajib

Set environment berikut di service Dokploy:

- APP_ENV=production
- APP_DEBUG=false
- APP_KEY=base64:... (wajib terisi)
- APP_URL=https://domain-anda
- DB_CONNECTION=mysql
- DB_HOST=host-db
- DB_PORT=3306
- DB_DATABASE=finboard
- DB_USERNAME=...
- DB_PASSWORD=...
- SESSION_DRIVER=file
- CACHE_STORE=file
- QUEUE_CONNECTION=database

Alasan:

- SESSION_DRIVER=file dan CACHE_STORE=file membuat web tetap stabil saat DB sibuk.
- QUEUE_CONNECTION=database tetap cocok dengan worker internal supervisor.

## 3) Deploy ulang penuh

Lakukan full rebuild (tanpa cache build lama), lalu redeploy.

## 4) Verifikasi setelah deploy

Dari terminal container app, jalankan:

```bash
php -v
php -m | grep -Ei "pdo_mysql|mysqli|mysqlnd"
php artisan optimize:clear
php artisan queue:restart
php artisan migrate --force
```

## 5) Cek proses internal container

Pastikan proses supervisor aktif (nginx, php-fpm, worker):

```bash
ps aux | grep -E "supervisord|php-fpm|nginx|queue:work" | grep -v grep
```

## 6) Jika masih 503 saat upload

Periksa log berikut di Dokploy:

- Web/Container logs (service utama)
- Nginx error log
- PHP-FPM stderr
- Laravel log di storage/logs

Gejala umum dan arti:

- 503 langsung setelah submit upload: origin process crash/restart.
- 502/503 acak: service port tidak stabil atau process manager tidak sehat.
- request lama lalu putus: timeout upstream atau resource limit (CPU/RAM).

## 7) Parameter resource yang disarankan

- CPU: minimal 1 vCPU
- Memory: minimal 1.5 GB (lebih aman 2 GB untuk upload besar)
- Storage: cukup untuk file sementara upload

## 8) Route upload yang dipakai

Upload masuk ke route berikut:

- [routes/web.php](routes/web.php#L98)

Controller upload:

- [app/Http/Controllers/UploadController.php](app/Http/Controllers/UploadController.php)
