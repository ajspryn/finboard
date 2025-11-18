# Email-Based PIN Authentication System

Sistem autentikasi berbasis email dengan PIN code 6 digit untuk aplikasi Finboard.

## 🚀 Fitur Utama

-   ✅ **Email-based Login**: Login menggunakan email terdaftar
-   ✅ **PIN Code Verification**: Verifikasi menggunakan kode PIN 6 digit
-   ✅ **Role-based Access**: Sistem role (admin, funding, lending)
-   ✅ **Email Templates**: Template email yang profesional
-   ✅ **Security Features**: PIN expiration, single-use codes
-   ✅ **Responsive UI**: Interface yang modern dan responsive

## 📋 Prerequisites

-   Laravel 11.x
-   PHP 8.1+
-   Database (MySQL/PostgreSQL/SQLite)
-   Email service (SMTP/Gmail/Mailgun/SendGrid)

## 🛠️ Installation & Setup

### 1. Database Migration

Jalankan migration untuk membuat tabel yang diperlukan:

```bash
php artisan migrate
```

### 2. Seed Example Users

Buat user contoh dengan role berbeda:

```bash
php artisan db:seed --class=UserSeeder
```

**Example Users:**

-   `admin@finboard.com` - Role: admin
-   `funding@finboard.com` - Role: funding
-   `lending@finboard.com` - Role: lending
-   `test@example.com` - Role: admin

Password default: `password123`

### 3. Email Configuration

Konfigurasi email di file `.env`:

```env
# SMTP Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@domain.com
MAIL_FROM_NAME="Finboard System"

# Gmail SMTP (Alternative)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls

# For development/testing (logs to file)
MAIL_MAILER=log
```

### 4. Start Application

```bash
php artisan serve
```

Akses aplikasi di: `http://localhost:8000`

## 🔐 Authentication Flow

### Step 1: Email Input

1. User mengakses halaman login
2. Memasukkan email yang terdaftar
3. Sistem memverifikasi email ada di database
4. Sistem mengecek role user

### Step 2: PIN Code Generation

1. Sistem generate PIN code 6 digit random
2. PIN code disimpan di database dengan expiration (10 menit)
3. Email dengan PIN code dikirim ke user

### Step 3: PIN Verification

1. User memasukkan PIN code 6 digit
2. Sistem memverifikasi PIN code
3. Jika valid, user di-loginkan dan diarahkan ke dashboard
4. PIN code di-mark sebagai used

## 📊 Database Schema

### Users Table (Extended)

```sql
- id (primary key)
- name (string)
- email (string, unique)
- password (hashed)
- role (enum: admin, funding, lending)
- timestamps
```

### Email PIN Codes Table

```sql
- id (primary key)
- email (string, indexed)
- pin_code (string, 6 chars)
- expires_at (timestamp)
- used_at (timestamp, nullable)
- timestamps
- indexes: email + expires_at
```

## 🎨 UI Components

### Login Form (`/auth/email`)

-   Email input field
-   Send PIN button dengan loading state
-   Error/success messages
-   Responsive design

### PIN Verification (`/auth/verify-pin`)

-   PIN input field (6 digits, auto-format)
-   Verify button dengan loading state
-   Resend PIN option
-   Back to email form link

## 🔒 Security Features

-   **PIN Expiration**: 10 menit dari waktu generate
-   **Single Use**: PIN hanya bisa digunakan sekali
-   **Rate Limiting**: Prevent brute force attacks
-   **Session Management**: Secure session handling
-   **CSRF Protection**: Built-in Laravel CSRF protection

## 🛡️ Role-Based Access Control

### Middleware Usage

```php
// Require authentication
Route::middleware(['auth'])->group(function () {
    // Routes for authenticated users
});

// Require specific role
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Routes only for admin
});

// Require multiple roles
Route::middleware(['auth', 'role:admin,funding'])->group(function () {
    // Routes for admin or funding
});
```

### Available Roles

-   **admin**: Full system access
-   **funding**: Funding-related features
-   **lending**: Lending-related features

## 📧 Email Templates

### PIN Code Email

-   Professional HTML template
-   Security warnings
-   Expiration notice
-   Responsive design

Location: `resources/views/emails/pin-code.blade.php`

## 🧪 Testing

### Manual Testing Steps

1. **Email Registration Test**:

    - Masukkan email yang terdaftar
    - Cek email untuk PIN code
    - Verifikasi format email

2. **PIN Verification Test**:

    - Masukkan PIN code yang benar
    - Test PIN code yang salah
    - Test PIN code expired

3. **Role Access Test**:
    - Login dengan user berbeda role
    - Test akses halaman sesuai role

### Automated Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=AuthTest
```

## 🚀 Production Deployment

### Pre-deployment Checklist

-   [ ] Email service configured
-   [ ] Database migrated
-   [ ] Users seeded
-   [ ] Environment variables set
-   [ ] SSL certificate installed
-   [ ] Domain configured

### Security Considerations

-   [ ] Use HTTPS in production
-   [ ] Set strong APP_KEY
-   [ ] Configure proper session settings
-   [ ] Set up monitoring and logging
-   [ ] Regular security updates

## 🐛 Troubleshooting

### Common Issues

**Email not received:**

-   Check spam/junk folder
-   Verify email configuration
-   Check mail logs: `tail -f storage/logs/laravel.log`

**PIN code expired:**

-   PIN codes expire in 10 minutes
-   User can request new PIN code

**Database connection error:**

-   Verify database credentials in `.env`
-   Run migrations: `php artisan migrate`

**Role access denied:**

-   Check user role in database
-   Verify middleware configuration

## 📝 API Reference

### Authentication Endpoints

```
GET  /                    - Login form
POST /auth/send-pin       - Send PIN to email
GET  /auth/verify-pin     - PIN verification form
POST /auth/verify-pin     - Verify PIN code
POST /auth/resend-pin     - Resend PIN code
POST /auth/logout         - Logout user
```

### Request/Response Examples

**Send PIN Request:**

```json
POST /auth/send-pin
{
  "email": "admin@finboard.com"
}
```

**PIN Verification Request:**

```json
POST /auth/verify-pin
{
  "pin": "123456"
}
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

This project is licensed under the MIT License.

---

**Need Help?** Contact the development team or check the Laravel documentation.
