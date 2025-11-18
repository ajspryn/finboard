<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailPinCode;
use App\Mail\PinCodeMail;
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
     * Send PIN code to email
     */
    public function sendPin(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->input('email');

        // Check if user exists and get their role
        $user = User::where('email', $email)->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email tidak terdaftar dalam sistem.'
                ], 404);
            }
            return back()->with('error', 'Email tidak terdaftar dalam sistem.');
        }

        // Generate and save PIN code
        $pinRecord = EmailPinCode::generateForEmail($email);

        // Send PIN via email
        try {
            Mail::to($email)->send(new PinCodeMail($pinRecord->pin_code));

            // Store email in session for PIN verification (only for web requests)
            if (!$request->expectsJson()) {
                session(['login_email' => $email]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode PIN telah dikirim ke email Anda.',
                    'email' => $email
                ]);
            }

            return redirect()->route('auth.verify-pin')->with('success', 'Kode PIN telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim email. Silakan coba lagi.'
                ], 500);
            }
            return back()->with('error', 'Gagal mengirim email. Silakan coba lagi.');
        }
    }

    /**
     * Show PIN verification form
     */
    public function showVerifyPinForm()
    {
        // Redirect if no email in session
        if (!session()->has('login_email')) {
            return redirect()->route('auth.login');
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
            return redirect()->route('auth.login')->with('error', 'Sesi login telah berakhir.');
        }

        // Verify PIN
        if (EmailPinCode::verifyPin($email, $pin)) {
            // Get user and login
            $user = User::where('email', $email)->first();

            if ($user) {
                Auth::login($user);

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

        if (!$email) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email diperlukan untuk mengirim ulang PIN.'
                ], 400);
            }
            return redirect()->route('auth.login')->with('error', 'Sesi login telah berakhir.');
        }

        // Generate new PIN
        $pinRecord = EmailPinCode::generateForEmail($email);

        // Send PIN via email
        try {
            Mail::to($email)->send(new PinCodeMail($pinRecord->pin_code));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kode PIN baru telah dikirim ke email Anda.',
                    'email' => $email
                ]);
            }

            return back()->with('success', 'Kode PIN baru telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim email. Silakan coba lagi.'
                ], 500);
            }
            return back()->with('error', 'Gagal mengirim email. Silakan coba lagi.');
        }
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
