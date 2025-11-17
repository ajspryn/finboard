# 📘 Technical Documentation - FinBoard

## Penjelasan Detail Komponen Aplikasi

---

## 🔐 Authentication System (PIN-Based)

### Cara Kerja Autentikasi PIN

#### 1. **Login Process**

```php
// AuthController.php - Method login()

public function login(Request $request)
{
    // 1. Validasi input PIN
    $request->validate([
        'pin' => 'required|string'
    ]);

    // 2. Ambil PIN dari input
    $inputPin = $request->input('pin');

    // 3. Ambil PIN yang benar dari .env
    $correctPin = env('DASHBOARD_PIN', '123456');

    // 4. Bandingkan PIN
    if ($inputPin === $correctPin) {
        // 5. Set session jika PIN benar
        session(['pin_verified' => true]);

        // 6. Redirect ke dashboard
        return redirect('/dashboard');
    }

    // 7. Kembali ke login jika PIN salah
    return back()->with('error', 'PIN yang Anda masukkan salah.');
}
```

#### 2. **Middleware CheckPin**

```php
// CheckPin.php

public function handle(Request $request, Closure $next): Response
{
    // Cek apakah session 'pin_verified' ada dan bernilai true
    if (!session()->has('pin_verified') || session('pin_verified') !== true) {
        // Jika tidak ada, redirect ke halaman login
        return redirect('/')->with('error', 'Silakan masukkan PIN terlebih dahulu.');
    }

    // Jika ada, lanjutkan request ke controller
    return $next($request);
}
```

#### 3. **Protected Routes**

```php
// web.php

// Route yang dilindungi CheckPin middleware
Route::middleware(['App\Http\Middleware\CheckPin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // Tambahkan route lain yang perlu proteksi di sini
});
```

---

## 📊 Dashboard Data Flow

### 1. **DashboardController**

```php
public function index()
{
    // Prepare dummy data
    $funding = [...];
    $lending = [...];
    $npf = [...];
    $monthlyTrends = [...];
    $npfDistribution = [...];

    // Pass to view
    return view('dashboard', compact(
        'funding',
        'lending',
        'npf',
        'monthlyTrends',
        'npfDistribution'
    ));
}
```

### 2. **View Processing (dashboard.blade.php)**

```blade
@extends('layouts.app')

@section('content')
    <!-- Kartu Funding -->
    <div class="card">
        <h3>Rp {{ number_format($funding['total'] / 1000000000, 2) }}M</h3>
        <!-- Data lainnya -->
    </div>
@endsection

@section('scripts')
    <script>
        // Render charts dengan ApexCharts
        const chart = new ApexCharts(element, options);
        chart.render();
    </script>
@endsection
```

---

## 🎨 Blade Template System

### Layout Structure

```
layouts/app.blade.php (Master Layout)
├── Head Section
│   ├── Meta tags
│   ├── CSS files (Vuexy)
│   └── @yield('styles')
├── Body
│   ├── Sidebar Menu (jika @auth)
│   ├── Navbar (jika @auth)
│   ├── Content Area
│   │   └── @yield('content')
│   └── Footer
└── Scripts
    ├── Core JS (Vuexy)
    └── @yield('scripts')
```

### View Inheritance

```blade
<!-- dashboard.blade.php -->
@extends('layouts.app')           <!-- Inherit from app.blade.php -->

@section('title', 'Dashboard')    <!-- Override title -->

@section('styles')                 <!-- Add custom CSS -->
    <link rel="stylesheet" href="...">
@endsection

@section('content')                <!-- Main content -->
    <div>...</div>
@endsection

@section('scripts')                <!-- Add custom JS -->
    <script>...</script>
@endsection
```

---

## 📈 Chart Implementation

### ApexCharts Integration

#### 1. **Line Chart (Tren Bulanan)**

```javascript
const monthlyTrendOptions = {
  series: [
    {
      name: "Funding",
      data: [22, 22.5, 23.2, 24, 24.5, 25], // in Billion
    },
    {
      name: "Lending",
      data: [28, 29, 29.8, 30.5, 31.2, 32],
    },
  ],
  chart: {
    height: 300,
    type: "line",
    toolbar: { show: false },
  },
  stroke: {
    curve: "smooth",
    width: 3,
  },
  colors: ["#7367F0", "#28C76F"],
  xaxis: {
    categories: ["Mei", "Jun", "Jul", "Agu", "Sep", "Okt"],
  },
  yaxis: {
    labels: {
      formatter: function (val) {
        return "Rp " + val.toFixed(1) + "M";
      },
    },
  },
};

const chart = new ApexCharts(
  document.querySelector("#monthlyTrendChart"),
  monthlyTrendOptions
);
chart.render();
```

#### 2. **Donut Chart (Distribusi NPF)**

```javascript
const npfDistributionOptions = {
  series: [40, 30, 30], // Percentage values
  chart: {
    height: 250,
    type: "donut",
  },
  labels: ["Kurang Lancar", "Diragukan", "Macet"],
  colors: ["#FF9F43", "#EA5455", "#343A40"],
  plotOptions: {
    pie: {
      donut: {
        size: "70%",
        labels: {
          show: true,
          total: {
            show: true,
            label: "Total NPF",
          },
        },
      },
    },
  },
};

const npfChart = new ApexCharts(
  document.querySelector("#npfDistributionChart"),
  npfDistributionOptions
);
npfChart.render();
```

---

## 🔄 Session Management

### Session Configuration

```php
// config/session.php

'driver' => env('SESSION_DRIVER', 'file'),
'lifetime' => env('SESSION_LIFETIME', 120),  // minutes
'expire_on_close' => false,
'files' => storage_path('framework/sessions'),
'cookie' => 'finboard_session',
```

### Session Usage

```php
// Set session
session(['pin_verified' => true]);
session(['user_data' => $data]);

// Get session
$isVerified = session('pin_verified');
$userData = session('user_data');

// Check if session exists
if (session()->has('pin_verified')) {
    // Session exists
}

// Remove specific session
session()->forget('pin_verified');

// Clear all sessions
session()->flush();
// atau
Session::flush();
```

---

## 🛡️ Security Considerations

### 1. **CSRF Protection**

```blade
<!-- Semua form POST harus include CSRF token -->
<form method="POST" action="/login">
    @csrf
    <!-- form fields -->
</form>
```

Laravel otomatis memvalidasi token ini.

### 2. **XSS Protection**

```blade
<!-- Blade automatically escapes output -->
{{ $variable }}  <!-- Safe: akan di-escape -->
{!! $variable !!}  <!-- Unsafe: tidak di-escape, hati-hati! -->
```

### 3. **SQL Injection Prevention**

```php
// Menggunakan Eloquent atau Query Builder (auto-escape)
DB::table('users')->where('pin', $pin)->first();  // Safe

// JANGAN gunakan raw query dengan input user
DB::raw("SELECT * WHERE pin = $pin");  // Dangerous!
```

### 4. **Session Security**

```php
// Regenerate session ID setelah login
session()->regenerate();

// Set secure cookie (hanya HTTPS)
'secure' => env('SESSION_SECURE_COOKIE', false),  // Set true di production

// HTTP only cookie (tidak bisa diakses JavaScript)
'http_only' => true,
```

---

## 🎯 Best Practices Applied

### 1. **Controller Best Practices**

```php
class DashboardController extends Controller
{
    // Single responsibility: hanya handle dashboard
    public function index()
    {
        // Prepare data
        $data = $this->prepareData();

        // Return view
        return view('dashboard', $data);
    }

    // Private helper method
    private function prepareData()
    {
        return [
            'funding' => [...],
            'lending' => [...],
            'npf' => [...]
        ];
    }
}
```

### 2. **Route Organization**

```php
// Group routes by middleware
Route::middleware(['CheckPin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // Tambahkan route lain dengan proteksi sama
});

// Named routes
Route::get('/dashboard', ...)->name('dashboard');
// Bisa dipanggil: route('dashboard')
```

### 3. **View Organization**

```
views/
├── layouts/
│   └── app.blade.php          # Master layout
├── auth/
│   └── pin.blade.php          # Auth related
├── components/
│   ├── card.blade.php         # Reusable components
│   └── chart.blade.php
└── dashboard.blade.php        # Page views
```

### 4. **Asset Management**

```blade
<!-- Use Laravel asset helper -->
<link rel="stylesheet" href="{{ asset('template/assets/css/core.css') }}">
<script src="{{ asset('template/assets/js/main.js') }}"></script>

<!-- Or direct path (jika sudah setup public) -->
<link rel="stylesheet" href="/template/assets/css/core.css">
```

---

## 🔧 Configuration Files

### Important Config Files

1. **config/app.php** - Application configuration

   - APP_NAME
   - APP_ENV
   - APP_DEBUG
   - Timezone
   - Locale

2. **config/session.php** - Session configuration

   - Driver
   - Lifetime
   - Cookie settings

3. **.env** - Environment variables
   ```env
   APP_NAME=FinBoard
   APP_ENV=local
   APP_DEBUG=true
   DASHBOARD_PIN=123456
   SESSION_DRIVER=file
   SESSION_LIFETIME=120
   ```

---

## 🐛 Debugging Tips

### 1. **Enable Debug Mode**

```env
APP_DEBUG=true  # di .env
```

### 2. **View Logs**

```bash
tail -f storage/logs/laravel.log
```

### 3. **Dump Variables**

```php
// In controller or view
dd($variable);  // Dump and die
dump($variable);  // Dump tanpa stop execution
```

```blade
<!-- In Blade template -->
@dump($variable)
@dd($variable)
```

### 4. **Check Session**

```php
// In controller
dd(session()->all());  // Lihat semua session
```

### 5. **Clear Cache**

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## 📊 Performance Optimization

### 1. **Production Caching**

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 2. **Asset Optimization**

- Minify CSS/JS
- Use CDN untuk libraries besar
- Enable gzip compression di web server
- Browser caching

### 3. **Database Optimization** (future)

```php
// Eager loading
$funding = Funding::with('transactions')->get();

// Select only needed columns
$funding = Funding::select('id', 'total', 'growth')->get();

// Use pagination
$funding = Funding::paginate(50);
```

---

## 🚀 Scaling Considerations

### Horizontal Scaling

1. **Session Driver**: Ganti dari `file` ke `redis` atau `database`

   ```env
   SESSION_DRIVER=redis
   ```

2. **Cache Driver**: Use Redis/Memcached

   ```env
   CACHE_DRIVER=redis
   ```

3. **Queue System**: Untuk background jobs
   ```env
   QUEUE_CONNECTION=redis
   ```

### Load Balancing

- Session harus centralized (Redis/Database)
- Static assets di CDN
- Database read replicas

---

## 📝 Code Comments & Documentation

### Good Comments

```php
/**
 * Validate and authenticate user with PIN
 *
 * @param Request $request
 * @return RedirectResponse
 */
public function login(Request $request)
{
    // Get PIN from environment (default: 123456)
    $correctPin = env('DASHBOARD_PIN', '123456');

    // Validate user input
    // ...
}
```

### Bad Comments

```php
// Get PIN
$pin = $request->pin;  // This is obvious

// Check if PIN is correct
if ($pin === $correctPin) {  // This is also obvious
    // ...
}
```

---

**Dokumentasi ini akan terus diperbarui seiring pengembangan aplikasi.**
