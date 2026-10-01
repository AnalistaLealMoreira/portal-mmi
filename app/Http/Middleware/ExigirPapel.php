<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso nas rotas: `papel:ADMIN` ou `papel:ADMIN,ADMIN_EMPRESA`.
 */
class ExigirPapel
{
    public function handle(Request $request, Closure $next, string ...$papeis): Response
    {
        abort_unless($request->user()?->temPapel(...$papeis), 403);

        return $next($request);
    }
}
