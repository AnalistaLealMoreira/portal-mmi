<?php

namespace App\Http\Controllers;

use App\Models\AcessoLog;
use App\Models\LinkBi;
use App\Models\Setor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LinkBiController extends Controller
{
    /** Lista os links de BI visíveis para o usuário logado (Admin vê todos). */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $setorId = (string) $request->query('setor', '');
        $busca = trim((string) $request->query('q', ''));
        $base = fn () => LinkBi::visivelPara($usuario)->where('bi_links_linkbi.ativo', true);

        $links = $base()
            ->with('setor')
            ->when($setorId !== '', fn ($q) => $q->where('bi_links_linkbi.setor_id', $setorId))
            ->when($busca !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('bi_links_linkbi.nome', 'like', "%{$busca}%")
                ->orWhere('bi_links_linkbi.descricao', 'like', "%{$busca}%")))
            ->ordenado()
            ->paginate(24)
            ->withQueryString();

        return view('links.index', [
            'links' => $links,
            'linksPorSetor' => $links->getCollection()->groupBy(fn ($link) => $link->setor->nome),
            'recentes' => $setorId === '' && $busca === '' && $links->onFirstPage() ? $this->recentes($request, $base()) : collect(),
            'setores' => Setor::whereIn('id', $base()->select('bi_links_linkbi.setor_id'))->orderBy('nome')->get(),
            'setorSelecionado' => $setorId,
        ]);
    }

    /** Até 4 links distintos que o próprio usuário abriu por último (e que ainda pode ver). */
    private function recentes(Request $request, $visiveis)
    {
        $ids = AcessoLog::where('usuario_id', $request->user()->id)
            ->whereIn('link_id', $visiveis->select('bi_links_linkbi.id'))
            ->selectRaw('link_id, MAX(acessado_em) as ultimo')
            ->groupBy('link_id')
            ->orderByDesc('ultimo')
            ->limit(4)
            ->pluck('ultimo', 'link_id');

        return LinkBi::with('setor')->whereIn('id', $ids->keys())->get()
            ->sortByDesc(fn ($link) => $ids[$link->id])
            ->each(fn ($link) => $link->ultimo_acesso = $ids[$link->id])
            ->values();
    }

    /**
     * Audita o acesso e mostra o relatório num iframe dentro do próprio portal
     * (o usuário não sai do portal nem abre outra aba).
     */
    public function acessar(Request $request, int $link): View
    {
        $link = LinkBi::visivelPara($request->user())->findOrFail($link);

        AcessoLog::create([
            'usuario_id' => $request->user()->id,
            'link_id' => $link->id,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return view('links.acessar', ['link' => $link]);
    }
}
