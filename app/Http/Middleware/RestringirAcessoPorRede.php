<?php

namespace App\Http\Middleware;

use App\Models\RedePermitida;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Diretores, administradores e usuários especiais acessam de qualquer rede.
 * Usuários normais só acessam quando o IP pertence a uma rede ativa cadastrada.
 *
 * Usa o REMOTE_ADDR (request->ip() sem proxies confiáveis configurados), igual
 * ao portal Django. Veja o README sobre proxy reverso.
 */
class RestringirAcessoPorRede
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && $usuario->isNormal() && ! $request->routeIs('logout') && ! RedePermitida::ipPermitido($request->ip())) {
            return response()->view('accounts.acesso_rede_negado', [
                'enderecoIp' => $request->ip() ?? 'desconhecido',
            ], 403);
        }

        return $next($request);
    }
}
