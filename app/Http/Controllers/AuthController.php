<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
     /**
      * Show the PIN login form
      */
     public function showLoginForm()
     {
          // Redirect to dashboard if already logged in
          if (session()->has('pin_verified') && session('pin_verified') === true) {
               return redirect('/dashboard');
          }

          return view('auth.pin');
     }

     /**
      * Handle PIN authentication
      */
     public function login(Request $request)
     {
          $request->validate([
               'pin' => 'required|string'
          ]);

          $inputPin = $request->input('pin');
          $correctPin = env('DASHBOARD_PIN', '123456');

          if ($inputPin === $correctPin) {
               // Store PIN verification in session
               session(['pin_verified' => true]);

               return redirect('/dashboard')->with('success', 'Login berhasil!');
          }

          return back()->with('error', 'PIN yang Anda masukkan salah.');
     }

     /**
      * Handle logout
      */
     public function logout()
     {
          Session::flush();
          return redirect('/')->with('success', 'Anda telah logout.');
     }
}
