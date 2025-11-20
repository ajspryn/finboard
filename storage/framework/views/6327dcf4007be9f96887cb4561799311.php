<!DOCTYPE html>
<html lang="id" class="light-style layout-wide customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="/template/assets/" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Login - FinBoard</title>

    <meta name="description" content="FinBoard - Login dengan Email" />
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
    <link rel="stylesheet" href="/template/assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="/template/assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="/template/assets/vendor/js/helpers.js"></script>
    <script src="/template/assets/js/config.js"></script>
</head>

<body>
    <!-- Content -->
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-4">
                <!-- Login -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-4 mt-2">
                            <a href="/" class="app-brand-link gap-2">
                                <span class="app-brand-logo demo">
                                    <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0.00172773 0V6.85718C0.00172773 6.85718 -0.133178 9.01581 1.98092 10.8999C4.09502 12.784 6.66081 12.784 6.66081 12.784L20.9467 12.784C20.9467 12.784 23.5125 12.784 25.6266 10.8999C27.7407 9.01581 27.6078 6.85718 27.6078 6.85718V0H0.00172773Z" fill="#7367F0" />
                                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M7.69824 16.4364L7.69824 18.3494L7.69824 21.0767L0.00172773 21.0767L0.00172773 15.8198C0.00172773 15.8198 1.96092 16.4364 7.69824 16.4364Z" fill="#161616" />
                                        <path opacity="0.06" fill-rule="evenodd" clip-rule="evenodd" d="M8.07751 15.8722V18.3494V21.0767H27.6078V15.8198C27.6078 15.8198 25.6487 16.4364 19.9113 16.4364L8.07751 15.8722Z" fill="#161616" />
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.77295 16.4364L27.6078 16.4364C27.6078 16.4364 27.6078 17.7191 27.6078 20.1214H7.77295V16.4364Z" fill="#7367F0" />
                                    </svg>
                                </span>
                                <span class="app-brand-text demo text-body fw-bold ms-1">FinBoard</span>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <h4 class="mb-1 pt-2 text-center">Selamat Datang! 👋</h4>
                        <p class="mb-4 text-center">Silakan masukkan email Anda untuk melanjutkan</p>

                        <!-- Alert Messages -->
                        <?php if(session('error')): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <?php echo e(session('error')); ?>

                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if(session('success')): ?>
                            <div class="alert alert-success alert-dismissible" role="alert">
                                <?php echo e(session('success')); ?>

                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="POST" action="<?php echo e(route('auth.send-pin')); ?>" id="loginForm">
                            <?php echo csrf_field(); ?>

                            <div class="mb-6 form-control-validation">
                                <label for="email" class="form-label">Email</label>
                                <input type="email"
                                       class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="email"
                                       name="email"
                                       value="<?php echo e(old('email')); ?>"
                                       placeholder="Masukkan email Anda"
                                       required
                                       autofocus>
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="mb-6">
                                <button type="submit"
                                        class="btn btn-primary d-grid w-100"
                                        id="sendPinBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                    <i class="ti ti-send me-2"></i>
                                    Kirim Kode PIN
                                </button>
                            </div>
                        </form>

                        <!-- Info Section -->
                        <div class="text-center">
                            <div class="border rounded p-3 bg-light mb-4">
                                <h6 class="mb-2">
                                    <i class="ti ti-info-circle text-info me-1"></i>
                                    Cara Login:
                                </h6>
                                <ol class="text-start small mb-0">
                                    <li>Masukkan email yang terdaftar</li>
                                    <li>Klik "Kirim Kode PIN"</li>
                                    <li>Periksa email Anda</li>
                                    <li>Masukkan kode PIN 6 digit</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Login -->
            </div>
        </div>
    </div>
    <!-- / Content -->

    <!-- Core JS -->
    <script src="/template/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/template/assets/vendor/libs/popper/popper.js"></script>
    <script src="/template/assets/vendor/js/bootstrap.js"></script>
    <script src="/template/assets/vendor/libs/node-waves/node-waves.js"></script>
    <script src="/template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="/template/assets/vendor/libs/hammer/hammer.js"></script>
    <script src="/template/assets/vendor/libs/typeahead-js/typeahead.js"></script>

    <!-- Main JS -->
    <script src="/template/assets/js/main.js"></script>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('sendPinBtn');
            const spinner = submitBtn.querySelector('.spinner-border');

            // Show loading state
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Mengirim...';
        });

        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>

</html>
<?php /**PATH /Users/ajspryn/Project/finboard/resources/views/auth/email.blade.php ENDPATH**/ ?>