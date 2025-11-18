<!DOCTYPE html>
<html lang="id" class="light-style layout-wide customizer-hide" dir="ltr" data-theme="theme-default" data-assets-path="/template/assets/" data-template="vertical-menu-template">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Verifikasi PIN - FinBoard</title>

    <meta name="description" content="FinBoard - Verifikasi Kode PIN" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
                <!-- Two Steps Verification -->
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

                        <h4 class="mb-1 pt-2 text-center">Verifikasi Kode PIN 💬</h4>
                        <p class="text-start mb-4">
                            Kami telah mengirim kode verifikasi ke email Anda.
                            <span class="fw-medium d-block mt-1 text-heading">{{ session('login_email') }}</span>
                        </p>
                        <p class="mb-0">Masukkan kode PIN 6 digit</p>

                        <!-- Alert Messages -->
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <!-- PIN Verification Form -->
                        <form method="POST" action="{{ route('auth.verify-pin') }}" id="pinForm">
                            @csrf

                            <div class="mb-6 form-control-validation">
                                <div class="auth-input-wrapper d-flex align-items-center justify-content-between numeral-mask-wrapper">
                                    <input type="tel" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" id="pin1" autofocus />
                                    <input type="tel" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" id="pin2" />
                                    <input type="tel" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" id="pin3" />
                                    <input type="tel" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" id="pin4" />
                                    <input type="tel" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" id="pin5" />
                                    <input type="tel" class="form-control auth-input h-px-50 text-center numeral-mask mx-sm-1 my-2" maxlength="1" id="pin6" />
                                </div>
                                <!-- Hidden field to store combined PIN -->
                                <input type="hidden" name="pin" id="pin" />
                                @error('pin')
                                    <div class="invalid-feedback d-block text-center mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-6">
                                <button type="submit"
                                        class="btn btn-primary d-grid w-100"
                                        id="verifyBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                    <i class="ti ti-login me-2"></i>
                                    Verifikasi & Login
                                </button>
                            </div>

                            <div class="text-center">
                                <button type="button"
                                        class="btn btn-link text-decoration-none"
                                        onclick="resendPin()">
                                    <i class="ti ti-refresh me-1"></i>
                                    Kirim Ulang Kode PIN
                                </button>
                            </div>
                        </form>

                        <!-- Back to Email Form -->
                        <div class="text-center mt-4">
                            <a href="{{ route('login') }}" class="text-muted small">
                                <i class="ti ti-arrow-left me-1"></i>
                                Ganti Email
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Two Steps Verification -->
            </div>
        </div>
    </div>
    <!-- / Content -->

    <!-- Resend PIN Form (Hidden) -->
    <form id="resendForm" method="POST" action="{{ route('auth.resend-pin') }}" style="display: none;">
        @csrf
    </form>

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
        document.getElementById('pinForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('verifyBtn');
            const spinner = submitBtn.querySelector('.spinner-border');

            // Combine PIN values
            const pinInputs = ['pin1', 'pin2', 'pin3', 'pin4', 'pin5', 'pin6'];
            let pinValue = '';
            for (let id of pinInputs) {
                pinValue += document.getElementById(id).value;
            }
            document.getElementById('pin').value = pinValue;

            // Show loading state
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Memverifikasi...';
        });

        // Auto-focus and navigation between PIN inputs
        const pinInputs = ['pin1', 'pin2', 'pin3', 'pin4', 'pin5', 'pin6'];
        pinInputs.forEach((id, index) => {
            const input = document.getElementById(id);
            input.addEventListener('input', function(e) {
                // Remove non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');

                // Auto-move to next input
                if (this.value.length === 1 && index < pinInputs.length - 1) {
                    document.getElementById(pinInputs[index + 1]).focus();
                }
            });

            input.addEventListener('keydown', function(e) {
                // Handle backspace
                if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                    document.getElementById(pinInputs[index - 1]).focus();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const pasteNumbers = paste.replace(/[^0-9]/g, '').slice(0, 6);

                for (let i = 0; i < pasteNumbers.length; i++) {
                    if (index + i < pinInputs.length) {
                        document.getElementById(pinInputs[index + i]).value = pasteNumbers[i];
                    }
                }

                // Focus on next empty input or last input
                const nextIndex = Math.min(index + pasteNumbers.length, pinInputs.length - 1);
                document.getElementById(pinInputs[nextIndex]).focus();
            });
        });

        // Resend PIN function
        function resendPin() {
            if (confirm('Kirim ulang kode PIN ke email Anda?')) {
                document.getElementById('resendForm').submit();
            }
        }

        // Auto-focus first PIN input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('pin1').focus();
        });
    </script>
</body>

</html>
