<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Manter conectado" desmarcado no login: o cookie de sessão expira ao fechar
 * o navegador (equivale ao `session.set_expiry(0)` do Django). Marcado, vale o
 * SESSION_LIFETIME.
 */
class ExpirarSessaoAoFecharNavegador
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession()) {
            config(['session.expire_on_close' => ! $request->session()->get('lembrar', false)]);
        }

        return $response;
    }
}
