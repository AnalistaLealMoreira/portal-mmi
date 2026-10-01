<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve a empresa do parâmetro `{empresa}` da rota e garante que o usuário
 * pode gerenciá-la (Admin: qualquer uma; Admin de Empresa: só a própria).
 * Responde 404 caso contrário, fechando o IDOR de trocar o id na URL.
 */
class EscopoEmpresa
{
    public function handle(Request $request, Closure $next): Response
    {
        $parametro = $request->route('empresa');
        $empresa = $parametro instanceof Empresa ? $parametro : Empresa::find($parametro);
        abort_if($empresa === null, 404);

        $usuario = $request->user();
        $podeGerenciar = $usuario->isAdmin()
            || ($usuario->isAdminEmpresa() && $usuario->empresaId() === $empresa->getKey());
        abort_unless($podeGerenciar, 404);

        $request->route()->setParameter('empresa', $empresa);

        return $next($request);
    }
}
