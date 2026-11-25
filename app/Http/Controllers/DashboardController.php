<?php

namespace App\Http\Controllers;

use App\Models\AcessoLog;
use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\LinkBi;
use App\Models\Setor;
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
            return view('core.dashboard', ['mostrarPainelAdmin' => true] + $this->painelAdmin($request));
        }

        return view('core.dashboard', ['mostrarIndicadores' => true] + $this->indicadoresEmpresa($usuario));
    }

    private function painelAdmin(Request $request): array
    {
        // Filtros: período (padrão últimos 30 dias, no fuso do portal) e área (setor do link).
        $hoje = now()->setTimezone(config('portal.timezone'));
        $de = $this->dia($request->query('de')) ?? $hoje->copy()->subDays(29);
        $ate = $this->dia($request->query('ate')) ?? $hoje->copy();
        if ($de->gt($ate)) {
            [$de, $ate] = [$ate, $de];
        }
        $setorId = $request->integer('setor') ?: null;

        $acessos = fn () => AcessoLog::query()
            ->join('bi_links_linkbi', 'bi_links_linkbi.id', '=', 'auditoria_acessolog.link_id')
            ->where('auditoria_acessolog.acessado_em', '>=', $de->copy()->startOfDay()->utc())
            ->where('auditoria_acessolog.acessado_em', '<=', $ate->copy()->endOfDay()->utc())
            ->when($setorId, fn ($q) => $q->where('bi_links_linkbi.setor_id', $setorId));

        $acessosPorUsuario = $acessos()
            ->join('accounts_usuario', 'accounts_usuario.id', '=', 'auditoria_acessolog.usuario_id')
            ->groupBy('accounts_usuario.id', 'accounts_usuario.first_name', 'accounts_usuario.last_name', 'accounts_usuario.username')
            ->orderByDesc('total')
            ->limit(15)
            ->get([
                'accounts_usuario.first_name', 'accounts_usuario.last_name', 'accounts_usuario.username',
                DB::raw('COUNT(auditoria_acessolog.id) as total'),
            ])
            ->map(fn ($linha) => [
                'nome' => trim($linha->first_name.' '.$linha->last_name) ?: $linha->username,
                'total' => (int) $linha->total,
            ]);

        $acessosPorLink = $acessos()
            ->groupBy('bi_links_linkbi.id', 'bi_links_linkbi.nome')
            ->orderByDesc('total')
            ->limit(15)
            ->get(['bi_links_linkbi.nome', DB::raw('COUNT(auditoria_acessolog.id) as total')])
            ->map(fn ($linha) => ['nome' => $linha->nome, 'total' => (int) $linha->total]);

        $acessosPorPerfil = $acessos()
            ->join('accounts_usuario', 'accounts_usuario.id', '=', 'auditoria_acessolog.usuario_id')
            ->groupBy('accounts_usuario.role')
            ->orderByDesc('total')
            ->get(['accounts_usuario.role', DB::raw('COUNT(auditoria_acessolog.id) as total')])
            ->map(fn ($linha) => [
                'nome' => Usuario::ROLES[$linha->role] ?? $linha->role,
                'total' => (int) $linha->total,
            ]);

        // Últimos 12 meses até o mês da data "Até", respeitando a área (meses no fuso do portal).
        $mesesAbrev = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $acessosPorMes = [];
        $ultimoMes = $ate->copy()->startOfMonth();
        for ($i = 11; $i >= 0; $i--) {
            $inicio = $ultimoMes->copy()->subMonthsNoOverflow($i);
            $acessosPorMes[] = [
                'nome' => $mesesAbrev[$inicio->month - 1].'/'.$inicio->format('y'),
                'total' => AcessoLog::query()
                    ->join('bi_links_linkbi', 'bi_links_linkbi.id', '=', 'auditoria_acessolog.link_id')
                    ->when($setorId, fn ($q) => $q->where('bi_links_linkbi.setor_id', $setorId))
                    ->where('auditoria_acessolog.acessado_em', '>=', $inicio->copy()->utc())
                    ->where('auditoria_acessolog.acessado_em', '<', $inicio->copy()->addMonthNoOverflow()->utc())
                    ->count(),
            ];
        }

        $areas = Setor::query()
            ->join('empresas_empresa', 'empresas_empresa.id', '=', 'setores_setor.empresa_id')
            ->orderBy('empresas_empresa.nome')
            ->orderBy('setores_setor.nome')
            ->get(['setores_setor.id', 'setores_setor.nome', 'empresas_empresa.nome as empresa']);

        return [
            'totalEmpresas' => Empresa::count(),
            'totalUsuarios' => Funcionario::count(),
            'usuariosOnline' => Usuario::where('last_seen', '>=', now()->subMinutes(config('portal.minutos_online')))->count(),
            'totalLinks' => LinkBi::count(),
            'acessosPorUsuario' => $acessosPorUsuario,
            'acessosPorLink' => $acessosPorLink,
            'acessosPorPerfil' => $acessosPorPerfil,
            'acessosPorMes' => $acessosPorMes,
            'areas' => $areas,
            'filtro' => ['de' => $de->format('Y-m-d'), 'ate' => $ate->format('Y-m-d'), 'setor' => $setorId],
        ];
    }

    private function dia(?string $valor): ?Carbon
    {
        if (! $valor) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $valor, config('portal.timezone'));
        } catch (\Throwable) {
            return null;
        }
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
