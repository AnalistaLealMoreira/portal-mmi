<?php

namespace App\Http\Controllers;

use App\Models\AcessoLog;
use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\LinkBi;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard diferente por papel:
 * - Admin global: visão consolidada de todas as empresas;
 * - Admin de Empresa e Diretor: indicadores da própria empresa;
 * - Normal e Especial: indicadores dos próprios acessos e relatórios visíveis.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $usuario = $request->user();

        if ($usuario->isAdmin()) {
            return view('core.dashboard', ['mostrarPainelAdmin' => true] + $this->painelAdmin());
        }

        return view('core.dashboard', ['mostrarIndicadores' => true] + $this->indicadoresEmpresa($usuario));
    }

    private function painelAdmin(): array
    {
        $linksPorEmpresa = LinkBi::query()
            ->join('setores_setor', 'setores_setor.id', '=', 'bi_links_linkbi.setor_id')
            ->join('empresas_empresa', 'empresas_empresa.id', '=', 'setores_setor.empresa_id')
            ->groupBy('empresas_empresa.nome')
            ->orderByDesc('total')
            ->get(['empresas_empresa.nome as empresa', DB::raw('COUNT(bi_links_linkbi.id) as total')])
            ->map(fn ($linha) => ['empresa' => $linha->empresa, 'total' => (int) $linha->total]);

        return [
            'totalEmpresas' => Empresa::count(),
            'totalUsuarios' => Funcionario::count(),
            'totalLinks' => LinkBi::count(),
            'linksPorEmpresa' => $linksPorEmpresa,
        ];
    }

    private function indicadoresEmpresa(Usuario $usuario): array
    {
        $agora = now();
        $linksVisiveis = fn () => LinkBi::visivelPara($usuario)->where('bi_links_linkbi.ativo', true);
        $acessosVisiveis = fn () => ($usuario->isNormal() || $usuario->isEspecial())
            ? AcessoLog::where('usuario_id', $usuario->id)
            : AcessoLog::visivelPara($usuario);

        $empresaId = $usuario->empresaId();
        $usuariosOnline = $empresaId
            ? Usuario::where('last_seen', '>=', $agora->copy()->subMinutes(config('portal.minutos_online')))
                ->whereHas('funcionario', fn ($q) => $q->where('empresa_id', $empresaId))
                ->count()
            : 0;

        $agrupar = fn (string $coluna) => $acessosVisiveis()
            ->join('bi_links_linkbi', 'bi_links_linkbi.id', '=', 'auditoria_acessolog.link_id')
            ->join('setores_setor', 'setores_setor.id', '=', 'bi_links_linkbi.setor_id')
            ->groupBy($coluna)
            ->orderByDesc('total')
            ->limit(10)
            ->get([DB::raw("$coluna as nome"), DB::raw('COUNT(auditoria_acessolog.id) as total')])
            ->map(fn ($linha) => ['nome' => $linha->nome, 'total' => (int) $linha->total]);

        // Últimos 7 dias, agrupados pelo dia no fuso do portal (o banco grava em UTC).
        $frequencia = [];
        $hojeLocal = $agora->copy()->setTimezone(config('portal.timezone'))->startOfDay();
        for ($i = 6; $i >= 0; $i--) {
            $inicio = $hojeLocal->copy()->subDays($i);
            $frequencia[] = [
                'dia' => $inicio->format('d/m'),
                'total' => $acessosVisiveis()
                    ->where('acessado_em', '>=', $inicio->copy()->setTimezone('UTC'))
                    ->where('acessado_em', '<', $inicio->copy()->addDay()->setTimezone('UTC'))
                    ->count(),
            ];
        }

        return [
            'totalLinksAtivos' => $linksVisiveis()->count(),
            'totalSetores' => $linksVisiveis()->distinct()->count('bi_links_linkbi.setor_id'),
            'usuariosOnline' => $usuariosOnline,
            'conexoesRecentes' => $acessosVisiveis()->where('acessado_em', '>=', $agora->copy()->subHours(24))->count(),
            'rankingSetores' => $agrupar('setores_setor.nome'),
            'topLinks' => $agrupar('bi_links_linkbi.nome'),
            'frequencia' => $frequencia,
        ];
    }
}
