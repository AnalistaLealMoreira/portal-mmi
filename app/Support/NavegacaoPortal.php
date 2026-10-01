<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\LinkBi;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Contexto do shell (sidebar/topbar) usado por `layouts/app.blade.php`:
 * empresa ativa, grupos de navegação filtrados pelo papel e "Meus links".
 * Cada item aponta para uma página real; o item ativo (e o título da topbar)
 * é resolvido pelo prefixo do nome da rota atual.
 */
class NavegacaoPortal
{
    private const GRUPOS = [
        [
            'titulo' => 'Painéis',
            'itens' => [
                ['label' => 'Visão geral', 'icone' => 'bi-grid-1x2', 'rota' => 'dashboard', 'ativo' => 'dashboard'],
                ['label' => 'Relatórios', 'icone' => 'bi-bar-chart-line', 'rota' => 'links.index', 'ativo' => 'links.*'],
            ],
        ],
        [
            'titulo' => 'Administração',
            'itens' => [
                ['label' => 'Usuários', 'icone' => 'bi-people', 'rota' => 'usuarios.index', 'ativo' => 'usuarios.*',
                    'papeis' => [Usuario::ADMIN, Usuario::ADMIN_EMPRESA]],
                ['label' => 'Empresas', 'icone' => 'bi-building', 'rota' => 'empresas.index', 'ativo' => 'empresas.*',
                    'papeis' => [Usuario::ADMIN]],
                ['label' => 'Setores', 'icone' => 'bi-diagram-3', 'rota' => 'setores.index', 'ativo' => 'setores.*',
                    'papeis' => [Usuario::ADMIN, Usuario::ADMIN_EMPRESA], 'porEmpresa' => true],
                ['label' => 'Links de BI', 'icone' => 'bi-link-45deg', 'rota' => 'links-admin.index', 'ativo' => 'links-admin.*',
                    'papeis' => [Usuario::ADMIN, Usuario::ADMIN_EMPRESA], 'porEmpresa' => true],
            ],
        ],
        [
            'titulo' => 'Sistema',
            'itens' => [
                ['label' => 'Redes permitidas', 'icone' => 'bi-shield-lock', 'rota' => 'redes.index', 'ativo' => 'redes.*',
                    'papeis' => [Usuario::ADMIN]],
                ['label' => 'Auditoria', 'icone' => 'bi-shield-check', 'rota' => 'auditoria.index', 'ativo' => 'auditoria.*',
                    'papeis' => [Usuario::ADMIN, Usuario::DIRETOR]],
            ],
        ],
    ];

    public static function contexto(Request $request): array
    {
        $usuario = $request->user();
        if (! $usuario) {
            return [];
        }

        [$empresaAtiva, $empresasDisponiveis] = self::empresaAtiva($request, $usuario);
        $grupos = self::grupos($request, $usuario, $empresaAtiva);

        return [
            'empresaAtiva' => $empresaAtiva,
            'empresasDisponiveis' => $empresasDisponiveis,
            'gruposNav' => $grupos,
            'tituloPaginaShell' => self::titulo($grupos),
            'sidebarSetores' => self::sidebarSetores($usuario),
        ];
    }

    /** Admin global: empresa escolhida na sessão (ou a primeira); demais: a própria. */
    private static function empresaAtiva(Request $request, Usuario $usuario): array
    {
        if ($usuario->isAdmin()) {
            $disponiveis = Empresa::orderBy('nome')->get();
            $ativa = $disponiveis->firstWhere('id', $request->session()->get('empresa_ativa_id')) ?? $disponiveis->first();

            return [$ativa, $disponiveis];
        }

        return [$usuario->funcionario?->empresa, null];
    }

    private static function grupos(Request $request, Usuario $usuario, ?Empresa $empresaAtiva): array
    {
        $grupos = [];
        foreach (self::GRUPOS as $grupo) {
            $itens = [];
            foreach ($grupo['itens'] as $item) {
                if (isset($item['papeis']) && ! $usuario->temPapel(...$item['papeis'])) {
                    continue;
                }
                if (! empty($item['porEmpresa']) && ! $empresaAtiva) {
                    continue;
                }
                $itens[] = [
                    'label' => $item['label'],
                    'icone' => $item['icone'],
                    'url' => route($item['rota'], ! empty($item['porEmpresa']) ? ['empresa' => $empresaAtiva->id] : []),
                    'ativo' => $request->routeIs($item['ativo']),
                ];
            }
            if ($itens) {
                $grupos[] = ['titulo' => $grupo['titulo'], 'itens' => $itens];
            }
        }

        return $grupos;
    }

    private static function titulo(array $grupos, string $padrao = 'Portal Axion'): string
    {
        foreach ($grupos as $grupo) {
            foreach ($grupo['itens'] as $item) {
                if ($item['ativo']) {
                    return $item['label'];
                }
            }
        }

        return $padrao;
    }

    /**
     * "Meus links": setores do usuário com os links visíveis em cada um.
     * Admin global e Admin de Empresa não têm (navegam pela administração).
     */
    private static function sidebarSetores(Usuario $usuario): Collection
    {
        if ($usuario->isAdmin() || $usuario->isAdminEmpresa()) {
            return collect();
        }

        $funcionario = $usuario->funcionario;
        $linksVisiveis = LinkBi::visivelPara($usuario)->where('bi_links_linkbi.ativo', true)->with('setor')->ordenado()->get();
        $linksPorSetor = $linksVisiveis->groupBy('setor_id');

        if ($usuario->isDiretor() && $funcionario) {
            $setores = $funcionario->empresa->setores()->orderBy('nome')->get();
        } elseif ($usuario->isEspecial() && $funcionario) {
            $setores = collect($funcionario->setor ? [$funcionario->setor] : []);
            foreach ($linksVisiveis as $link) {
                if (! $setores->contains('id', $link->setor_id)) {
                    $setores->push($link->setor);
                }
            }
        } elseif ($funcionario?->setor) {
            $setores = collect([$funcionario->setor]);
        } else {
            $setores = collect();
        }

        return $setores->each(fn ($setor) => $setor->setRelation('linksVisiveis', $linksPorSetor->get($setor->id, collect())));
    }
}
