<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mesmos cabeçalhos que o portal Django enviava (XFrameOptions, nosniff,
 * Referrer-Policy) e, em produção, redirecionamento para HTTPS + HSTS.
 */
class CabecalhosDeSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('portal.force_https') && ! $request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (config('portal.force_https') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age='.config('portal.hsts_seconds').'; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
