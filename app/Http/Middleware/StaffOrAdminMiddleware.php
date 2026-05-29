<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffOrAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->isAdminOrStaff()) {
            return response()->json([
                'message' => 'Quyền truy cập bị từ chối. Bạn không có quyền thực hiện thao tác này.'
            ], 403);
        }

        return $next($request);
    }
}
