<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevToolsMiddleware
{
    /**
     * Temporary dev routes — only available when DEV_TOOLS_ENABLED=true in .env.
     * Remove these routes before production.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!filter_var(env('DEV_TOOLS_ENABLED', false), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json([
                'success' => false,
                'message' => 'Not found',
            ], 404);
        }

        return $next($request);
    }
}
