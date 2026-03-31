<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleWritingRequiresActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $user = $request->user();
        if ($user !== null && ! $user->is_active) {
            return response()->json([
                'message' => 'Solo los usuarios activos pueden crear o modificar artículos.',
            ], 403);
        }

        return $next($request);
    }
}
