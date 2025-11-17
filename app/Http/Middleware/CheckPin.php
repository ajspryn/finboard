<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPin
{
     /**
      * Handle an incoming request.
      *
      * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
      */
     public function handle(Request $request, Closure $next): Response
     {
          // Check if PIN is verified in session
          if (!session()->has('pin_verified') || session('pin_verified') !== true) {
               return redirect('/')->with('error', 'Silakan masukkan PIN terlebih dahulu.');
          }

          return $next($request);
     }
}
