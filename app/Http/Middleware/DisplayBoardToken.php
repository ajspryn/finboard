<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisplayBoardToken
{
     public function handle(Request $request, Closure $next): Response
     {
          $configToken = config('app.display_board_token');

          if (empty($configToken)) {
               abort(403, 'Display board token not configured.');
          }

          $requestToken = $request->query('token');

          if (!$requestToken || !hash_equals($configToken, $requestToken)) {
               abort(403, 'Token tidak valid.');
          }

          return $next($request);
     }
}
