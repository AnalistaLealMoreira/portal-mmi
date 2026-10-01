<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Atualiza `last_seen` (usado no indicador "Usuários Online"), no máximo uma vez por minuto. */
class AtualizarUltimoAcesso
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $usuario = $request->user();
        if ($usuario) {
            $agora = now();
            if (! $usuario->last_seen || $usuario->last_seen->diffInSeconds($agora) > config('portal.intervalo_last_seen')) {
                Usuario::whereKey($usuario->getKey())->update(['last_seen' => $agora]);
            }
        }

        return $response;
    }
}
