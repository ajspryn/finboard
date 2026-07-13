<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailPinCode;
use App\Mail\PinCodeMail;
use App\Services\WhatsAppOtpSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Show the email login form
     */
    public function showLoginForm()
    {
        // Redirect to dashboard if already logged in
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.email');
    }

    /**
     * Send PIN code to default channel (WhatsApp first, then email fallback)
     */
    public function sendPin(Request $request)
    {
        $request->validate([
            'identifier' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'whatsapp_number' => ['nullable', 'string', 'max:20', 'regex:/^(\\+?62|0)[0-9]{8,18}$/'],
            'channel' => 'nullable|in:whatsapp,email',
        ]);

        $identifier = trim((string) ($request->input('identifier') ?? $request->input('email') ?? ''));

        if ($identifier === '') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau nomor WhatsApp wajib diisi.'
                ], 422);
            }

            return back()->with('error', 'Email atau nomor WhatsApp wajib diisi.');
        }

        $user = $this->findUserByIdentifier($identifier);

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau nomor WhatsApp tidak terdaftar dalam sistem.'
                ], 404);
            }

            return back()->with('error', 'Email atau nomor WhatsApp tidak terdaftar dalam sistem.');
        }

        // Force user to provide WhatsApp number on first login if missing.
        if (!$this->hasWhatsAppNumber($user)) {
            $inputWhatsapp = trim((string) $request->input('whatsapp_number', ''));

            if ($inputWhatsapp === '') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'requires_whatsapp_number' => true,
                        'message' => 'Nomor WhatsApp belum terdaftar. Silakan isi nomor WhatsApp terlebih dahulu.',
                        'email' => $user->email,
                    ], 422);
                }

                return back()
                    ->withInput([
                        'identifier' => $user->email,
                    ])
                    ->with('require_whatsapp_number', true)
                    ->with('require_whatsapp_for_email', $user->email)
                    ->with('info', 'Nomor WhatsApp Anda belum terdaftar. Silakan isi nomor WhatsApp untuk melanjutkan login.');
            }

            $normalizedWhatsapp = $this->normalizePhoneNumber($inputWhatsapp);
            if (!$normalizedWhatsapp) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'requires_whatsapp_number' => true,
                        'message' => 'Format nomor WhatsApp tidak valid. Gunakan awalan 0 atau 62.',
                    ], 422);
                }

                return back()
                    ->withInput([
                        'identifier' => $user->email,
                        'whatsapp_number' => $inputWhatsapp,
                    ])
                    ->with('require_whatsapp_number', true)
                    ->with('require_whatsapp_for_email', $user->email)
                    ->with('error', 'Format nomor WhatsApp tidak valid. Gunakan awalan 0 atau 62.');
            }

            $numberExists = User::where('whatsapp_number', $normalizedWhatsapp)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($numberExists) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'requires_whatsapp_number' => true,
                        'message' => 'Nomor WhatsApp tersebut sudah digunakan akun lain.',
                    ], 422);
                }

                return back()
                    ->withInput([
                        'identifier' => $user->email,
                        'whatsapp_number' => $inputWhatsapp,
                    ])
                    ->with('require_whatsapp_number', true)
                    ->with('require_whatsapp_for_email', $user->email)
                    ->with('error', 'Nomor WhatsApp tersebut sudah digunakan akun lain.');
            }

            $user->update([
                'whatsapp_number' => $normalizedWhatsapp,
            ]);

            $user->refresh();
        }

        // Generate and save PIN code
        $pinRecord = EmailPinCode::generateForEmail($user->email);

        try {
            $requestedChannel = $request->input('channel');
            $deliveryResult = $this->sendPinByChannel($user, $pinRecord->pin_code, $requestedChannel);

            if (!$request->expectsJson()) {
                session([
                    'login_email' => $user->email,
                    'login_has_whatsapp' => $this->hasWhatsAppNumber($user),
                    'login_whatsapp_number' => $user->whatsapp_number,
                    'login_last_pin_channel' => $deliveryResult['channel'],
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $deliveryResult['message'],
                    'email' => $user->email,
                    'channel' => $deliveryResult['channel'],
                    'available_channels' => $this->availableChannels($user),
                ]);
            }

            return redirect()->route('auth.verify-pin.form')->with('success', $deliveryResult['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim kode PIN. Silakan coba lagi.'
                ], 500);
            }

            return back()->with('error', 'Gagal mengirim kode PIN. Silakan coba lagi.');
        }
    }

    /**
     * Show PIN verification form
     */
    public function showVerifyPinForm()
    {
        // Redirect if no email in session
        if (!session()->has('login_email')) {
            return redirect()->route('login');
        }

        return response()->view('auth.verify-pin')->header('Content-Type', 'text/html');
    }

    /**
     * Verify PIN code and login user
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6'
        ]);

        $email = $request->expectsJson() ? $request->input('email') : session('login_email');
        $pin = $request->input('pin');

        if (!$email) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email diperlukan untuk verifikasi PIN.'
                ], 400);
            }
            return redirect()->route('login')->with('error', 'Sesi login telah berakhir.');
        }

        // Verify PIN
        if (EmailPinCode::verifyPin($email, $pin)) {
            // Get user and login
            $user = User::where('email', $email)->first();

            if ($user) {
                Auth::login($user);

                // Update last login timestamp
                $user->updateLastLogin();

                // Set session pin_verified for layout
                session(['pin_verified' => true]);

                // Clear session (only for web requests)
                if (!$request->expectsJson()) {
                    session()->forget('login_email');
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Login berhasil! Selamat datang, ' . $user->name,
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role
                        ]
                    ]);
                }

                return redirect('/dashboard')->with('success', 'Login berhasil! Selamat datang, ' . $user->name);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode PIN salah atau telah kadaluarsa.'
            ], 401);
        }

        return back()->with('error', 'Kode PIN salah atau telah kadaluarsa.');
    }

    /**
     * Resend PIN code
     */
    public function resendPin(Request $request)
    {
        $email = $request->expectsJson() ? $request->input('email') : session('login_email');
        $requestedChannel = $request->input('channel');

        if (!$email) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email diperlukan untuk mengirim ulang PIN.'
                ], 400);
            }
            return redirect()->route('login')->with('error', 'Sesi login telah berakhir.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan.'
                ], 404);
            }

            return redirect()->route('login')->with('error', 'User tidak ditemukan.');
        }

        if ($requestedChannel && !in_array($requestedChannel, $this->availableChannels($user), true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Channel pengiriman PIN tidak valid.'
                ], 422);
            }

            return back()->with('error', 'Channel pengiriman PIN tidak valid.');
        }

        // Generate new PIN
        $pinRecord = EmailPinCode::generateForEmail($email);

        try {
            $deliveryResult = $this->sendPinByChannel($user, $pinRecord->pin_code, $requestedChannel);

            if (!$request->expectsJson()) {
                session(['login_last_pin_channel' => $deliveryResult['channel']]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $deliveryResult['message'],
                    'email' => $email,
                    'channel' => $deliveryResult['channel'],
                    'available_channels' => $this->availableChannels($user),
                ]);
            }

            return back()->with('success', $deliveryResult['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim kode PIN. Silakan coba lagi.'
                ], 500);
            }

            return back()->with('error', 'Gagal mengirim kode PIN. Silakan coba lagi.');
        }
    }

    private function sendPinByChannel(User $user, string $pinCode, ?string $requestedChannel = null): array
    {
        $defaultChannel = $this->hasWhatsAppNumber($user) ? 'whatsapp' : 'email';
        $channel = $requestedChannel ?: $defaultChannel;

        if ($channel === 'whatsapp' && !$this->hasWhatsAppNumber($user)) {
            $channel = 'email';
        }

        if ($channel === 'whatsapp') {
            try {
                app(WhatsAppOtpSender::class)->sendOtp($user, $pinCode);

                return [
                    'channel' => 'whatsapp',
                    'message' => 'Kode PIN telah dikirim ke WhatsApp Anda.',
                ];
            } catch (\Throwable $e) {
                report($e);

                $this->sendPinViaEmail($user->email, $pinCode);

                return [
                    'channel' => 'email',
                    'message' => 'Pengiriman via WhatsApp gagal. Kode PIN dikirim ke email Anda.',
                ];
            }
        }

        $this->sendPinViaEmail($user->email, $pinCode);

        return [
            'channel' => 'email',
            'message' => 'Kode PIN telah dikirim ke email Anda.',
        ];
    }

    private function sendPinViaEmail(string $email, string $pinCode): void
    {
        Mail::to($email)->send(new PinCodeMail($pinCode));
    }

    private function findUserByIdentifier(string $identifier): ?User
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $identifier)->first();
        }

        $normalizedPhone = $this->normalizePhoneNumber($identifier);

        if (!$normalizedPhone) {
            return null;
        }

        return User::where('whatsapp_number', $normalizedPhone)->first();
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/[^0-9]/', '', $phone);

        if (!$normalized) {
            return null;
        }

        if (str_starts_with($normalized, '0')) {
            $normalized = '62' . substr($normalized, 1);
        }

        if (str_starts_with($normalized, '62')) {
            return $normalized;
        }

        return null;
    }

    private function hasWhatsAppNumber(User $user): bool
    {
        return !empty($user->whatsapp_number);
    }

    private function availableChannels(User $user): array
    {
        if ($this->hasWhatsAppNumber($user)) {
            return ['whatsapp', 'email'];
        }

        return ['email'];
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        Auth::logout();
        Session::flush();
        return redirect('/')->with('success', 'Anda telah logout.');
    }
}
