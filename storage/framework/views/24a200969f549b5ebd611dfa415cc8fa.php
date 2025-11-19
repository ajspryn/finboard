<!DOCTYPE html>
<html lang="id" class="light-style layout-navbar-fixed layout-menu-fixed layout-compact layout-menu-collapsed" dir="ltr" data-theme="theme-default" data-assets-path="/template/assets/" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?php echo $__env->yieldContent('title', 'Dashboard Bank'); ?></title>

    <meta name="description" content="Dashboard Bank - Aplikasi Monitoring Keuangan" />
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/template/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="/template/assets/vendor/fonts/fontawesome.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
    <link rel="stylesheet" href="/template/assets/vendor/fonts/flag-icons.css" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="/template/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/template/assets/vendor/css/theme-default.css" />
    <link rel="stylesheet" href="/template/assets/css/demo.css" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="/template/assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="/template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="/template/assets/vendor/libs/typeahead-js/typeahead.css" />
    <link rel="stylesheet" href="/template/assets/vendor/libs/apex-charts/apex-charts.css" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="/template/assets/vendor/css/pages/page-auth.css" />

    <?php echo $__env->yieldContent('styles'); ?>

    <!-- Helpers -->
    <script src="/template/assets/vendor/js/helpers.js"></script>
    <script src="/template/assets/vendor/js/template-customizer.js"></script>
    <script src="/template/assets/js/config.js"></script>
</head>

<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <?php if(session('pin_verified')): ?>
            <!-- Menu -->
            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="/dashboard" class="app-brand-link">
                        <span class="app-brand-logo demo">
                            <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M0.00172773 0V6.85718C0.00172773 6.85718 -0.133178 9.01581 1.98092 10.8999C4.09502 12.784 6.66081 12.784 6.66081 12.784L20.9467 12.784C20.9467 12.784 23.5125 12.784 25.6266 10.8999C27.7407 9.01581 27.6078 6.85718 27.6078 6.85718V0H0.00172773Z" fill="#7367F0" />
                                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M7.69824 16.4364L7.69824 18.3494L7.69824 21.0767L0.00172773 21.0767L0.00172773 15.8198C0.00172773 15.8198 1.96092 16.4364 7.69824 16.4364Z" fill="#161616" />
                                <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M8.07751 15.8722V18.3494V21.0767H27.6078V15.8198C27.6078 15.8198 25.6487 16.4364 19.9113 16.4364L8.07751 15.8722Z" fill="#161616" />
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.77295 16.4364L27.6078 16.4364C27.6078 16.4364 27.6078 17.7191 27.6078 20.1214H7.77295V16.4364Z" fill="#7367F0" />
                            </svg>
                        </span>
                        <span class="app-brand-text demo menu-text fw-bold">FinBoard</span>
                    </a>

                    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                        <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
                        <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <!-- Dashboard -->
                    <li class="menu-item <?php echo e(request()->is('dashboard') ? 'active' : ''); ?>">
                        <a href="/dashboard" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-smart-home"></i>
                            <div data-i18n="Dashboard">Dashboard</div>
                        </a>
                    </li>

                    <!-- Daily Activity (hanya untuk admin dan pengurus) -->
                    <?php if(auth()->user()->role === 'admin' || auth()->user()->role === 'pengurus'): ?>
                    <li class="menu-item <?php echo e(request()->is('daily-activity') ? 'active' : ''); ?>">
                        <a href="/daily-activity" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-calendar-event"></i>
                            <div data-i18n="DailyActivity">Daily Activity Karyawan</div>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Module sections -->
                    <?php if(auth()->user()->role === 'admin' || auth()->user()->role === 'funding' || auth()->user()->role === 'lending'): ?>
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Modul Upload</span>
                    </li>

                    <!-- Upload Data (admin dan lending) -->
                    <?php if(auth()->user()->role === 'admin' || auth()->user()->role === 'lending'): ?>
                    <li class="menu-item <?php echo e(request()->is('upload') ? 'active' : ''); ?>">
                        <a href="/upload" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-upload"></i>
                            <div data-i18n="Upload Pembiayaan">Upload Pembiayaan</div>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Upload Funding (admin dan funding) -->
                    <?php if(auth()->user()->role === 'admin' || auth()->user()->role === 'funding'): ?>
                    <li class="menu-item <?php echo e(request()->is('funding') ? 'active' : ''); ?>">
                        <a href="/funding" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-file-upload"></i>
                            <div data-i18n="Upload Tabungan & Deposito">Upload Tabungan & Deposito</div>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php endif; ?>


                    <!-- User Settings (hanya untuk admin) -->
                    <?php if(auth()->user()->role === 'admin'): ?>

                    <!-- Settings sections -->
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">Pengaturan</span>
                    </li>

                    <li class="menu-item <?php echo e(request()->is('user-settings') ? 'active' : ''); ?>">
                        <a href="/user-settings" class="menu-link">
                            <i class="menu-icon tf-icons ti ti-user-cog"></i>
                            <div data-i18n="UserSettings">Pengaturan User</div>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </aside>
            <!-- / Menu -->
            <?php endif; ?>

            <!-- Layout container -->
            <div class="layout-page">

                <?php if(session('pin_verified')): ?>
                <!-- Navbar -->
                <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
                        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
                            <i class="ti ti-menu-2 ti-sm"></i>
                        </a>
                    </div>

                    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
                        <div class="navbar-nav align-items-center">
                            <div class="nav-item navbar-search-wrapper mb-0">
                                <span class="d-none d-md-inline-block text-muted">Dashboard Bank - Monitoring Real-time</span>
                            </div>
                        </div>

                        <ul class="navbar-nav flex-row align-items-center ms-auto">
                            <!-- Period Filter (hanya di dashboard) -->
                            <?php if(request()->is('dashboard')): ?>
                            <li class="nav-item me-3">
                                <div class="d-flex align-items-center gap-2">
                                    <!-- Filter Range Tanggal -->
                                    <small class="text-muted">Tanggal:</small>
                                    <select id="filterStartDay" class="form-select form-select-sm" style="width: 70px;">
                                        <option value="">Dari</option>
                                        <?php for($d = 1; $d <= 31; $d++): ?>
                                            <option value="<?php echo e(str_pad($d, 2, '0', STR_PAD_LEFT)); ?>"><?php echo e($d); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <span class="text-muted" style="font-size: 10px;">-</span>
                                    <select id="filterEndDay" class="form-select form-select-sm" style="width: 70px;">
                                        <option value="">S/d</option>
                                        <?php for($d = 1; $d <= 31; $d++): ?>
                                            <option value="<?php echo e(str_pad($d, 2, '0', STR_PAD_LEFT)); ?>"><?php echo e($d); ?></option>
                                        <?php endfor; ?>
                                    </select>

                                    <!-- Filter Bulan & Tahun -->
                                    <select id="filterMonth" class="form-select form-select-sm" style="width: 110px;">
                                        <option value="">Pilih Bulan</option>
                                        <option value="01">Januari</option>
                                        <option value="02">Februari</option>
                                        <option value="03">Maret</option>
                                        <option value="04">April</option>
                                        <option value="05">Mei</option>
                                        <option value="06">Juni</option>
                                        <option value="07">Juli</option>
                                        <option value="08">Agustus</option>
                                        <option value="09">September</option>
                                        <option value="10">Oktober</option>
                                        <option value="11">November</option>
                                        <option value="12">Desember</option>
                                    </select>
                                    <select id="filterYear" class="form-select form-select-sm" style="width: 85px;">
                                        <option value="">Tahun</option>
                                        <?php
                                            $currentYear = date('Y');
                                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                                echo "<option value=\"{$y}\">{$y}</option>";
                                            }
                                        ?>
                                    </select>

                                    <button type="button" class="btn btn-sm btn-primary" onclick="applyFilters()">
                                        <i class="ti ti-filter ti-xs"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearFilters()">
                                        <i class="ti ti-x ti-xs"></i>
                                    </button>
                                </div>
                            </li>
                            <?php endif; ?>

                            <!-- User -->
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <span class="avatar-initial rounded-circle bg-label-primary">A</span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <span class="avatar-initial rounded-circle bg-label-primary"><?php echo e(strtoupper(substr(auth()->user()->name, 0, 1))); ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <span class="fw-medium d-block"><?php echo e(auth()->user()->name); ?></span>
                                                    <small class="text-muted"><?php echo e(auth()->user()->role === 'admin' ? 'Administrator' : (auth()->user()->role === 'pengurus' ? 'Pengurus' : (auth()->user()->role === 'funding' ? 'Funding Manager' : 'Lending Manager'))); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/logout">
                                            <i class="ti ti-logout me-2 ti-sm"></i>
                                            <span class="align-middle">Logout</span>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!--/ User -->
                        </ul>
                    </div>
                </nav>
                <!-- / Navbar -->
                <?php endif; ?>

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">

                        <?php if(session('success')): ?>
                        <div class="alert alert-success alert-dismissible" role="alert">
                            <?php echo e(session('success')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php if(session('error')): ?>
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            <?php echo e(session('error')); ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>

                        <?php echo $__env->yieldContent('content'); ?>
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    <footer class="content-footer footer bg-footer-theme">
                        <div class="container-xxl">
                            <div class="footer-container d-flex align-items-center justify-content-between py-2 flex-md-row flex-column">
                                <div>
                                    © <?php echo e(date('Y')); ?>, Dashboard Bank
                                </div>
                            </div>
                        </div>
                    </footer>
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>

        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="/template/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/template/assets/vendor/libs/popper/popper.js"></script>
    <script src="/template/assets/vendor/js/bootstrap.js"></script>
    <script src="/template/assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="/template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/template/assets/vendor/libs/hammer/hammer.js"></script>
    <script src="/template/assets/vendor/libs/i18n/i18n.js"></script>
    <script src="/template/assets/vendor/libs/typeahead-js/typeahead.js"></script>
    <script src="/template/assets/vendor/js/menu.js"></script>

    <!-- Vendors JS -->
    <script src="/template/assets/vendor/libs/apex-charts/apexcharts.js"></script>

    <!-- Main JS -->
    <script src="/template/assets/js/main.js"></script>

    <script>
        // Apply Filters Function
        function applyFilters() {
            const startDay = document.getElementById('filterStartDay').value;
            const endDay = document.getElementById('filterEndDay').value;
            const month = document.getElementById('filterMonth').value;
            const year = document.getElementById('filterYear').value;

            let url = '/dashboard?';
            const params = [];

            if (startDay) params.push('start_day=' + startDay);
            if (endDay) params.push('end_day=' + endDay);
            if (month) params.push('month=' + month);
            if (year) params.push('year=' + year);

            if (params.length > 0) {
                url += params.join('&');
            }

            window.location.href = url;
        }

        // Clear All Filters
        function clearFilters() {
            window.location.href = '/dashboard';
        }

        // Set filter values on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);

            const startDay = urlParams.get('start_day');
            const endDay = urlParams.get('end_day');
            const month = urlParams.get('month');
            const year = urlParams.get('year');

            // Set values from URL or default to current month/year
            if (document.getElementById('filterStartDay')) {
                document.getElementById('filterStartDay').value = startDay || '';
            }
            if (document.getElementById('filterEndDay')) {
                document.getElementById('filterEndDay').value = endDay || '';
            }
            if (document.getElementById('filterMonth')) {
                // Default ke bulan berjalan jika tidak ada parameter
                const currentMonth = new Date().getMonth() + 1;
                const monthStr = String(currentMonth).padStart(2, '0');
                document.getElementById('filterMonth').value = month || monthStr;
            }
            if (document.getElementById('filterYear')) {
                // Default ke tahun berjalan jika tidak ada parameter
                const currentYear = new Date().getFullYear();
                document.getElementById('filterYear').value = year || currentYear;
            }

            // Auto-apply default filter if no params in URL - HANYA DI HALAMAN DASHBOARD
            const currentPath = window.location.pathname;
            if (currentPath === '/dashboard' && !month && !year && !startDay && !endDay) {
                // Set default but don't redirect, let controller handle it
                const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');
                const currentYear = new Date().getFullYear();

                // Redirect with default params
                window.location.href = '/dashboard?month=' + currentMonth + '&year=' + currentYear;
            }
        });

        // Enter key to apply filter
        const filterElements = ['filterStartDay', 'filterEndDay', 'filterMonth', 'filterYear'];
        filterElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') applyFilters();
                });
            }
        });
    </script>

    <script>
        // Auto-collapse sidebar on page load
        document.addEventListener('DOMContentLoaded', function() {
            const htmlEl = document.documentElement;
            if (!htmlEl.classList.contains('layout-menu-collapsed')) {
                htmlEl.classList.add('layout-menu-collapsed');
            }

            // Store preference
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem('menuCollapsed', 'true');
            }
        });
    </script>

    <?php echo $__env->yieldContent('scripts'); ?>
</body>

</html>
<?php /**PATH /Users/ajspryn/Project/finboard/resources/views/layouts/app.blade.php ENDPATH**/ ?>