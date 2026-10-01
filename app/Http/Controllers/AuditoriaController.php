<?php

namespace App\Http\Controllers;

use App\Models\AcessoLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** Painel de auditoria de acessos (Admin: tudo; Diretor: a própria empresa). */
class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $acessos = AcessoLog::visivelPara($request->user())
            ->with(['usuario', 'link.setor'])
            ->when($request->query('funcionario'), fn ($q, $id) => $q->where('usuario_id', $id))
            ->when($request->query('link'), fn ($q, $id) => $q->where('link_id', $id))
            ->when(trim((string) $request->query('q', '')), fn ($q, $busca) => $q->where(fn ($w) => $w
                ->whereHas('usuario', fn ($u) => $u->where(fn ($x) => $x
                    ->where('first_name', 'like', "%{$busca}%")
                    ->orWhere('last_name', 'like', "%{$busca}%")
                    ->orWhere('username', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%")))
                ->orWhereHas('link', fn ($l) => $l->where('nome', 'like', "%{$busca}%"))))
            ->when($this->dia($request->query('de')), fn ($q, $dia) => $q->where('acessado_em', '>=', $dia->startOfDay()->utc()))
            ->when($this->dia($request->query('ate')), fn ($q, $dia) => $q->where('acessado_em', '<=', $dia->endOfDay()->utc()))
            ->orderByDesc('acessado_em')
            ->paginate(50)
            ->withQueryString();

        return view('auditoria.index', ['acessos' => $acessos]);
    }

    /** Data do filtro (AAAA-MM-DD) no fuso do portal; inválida ou vazia vira null. */
    private function dia(mixed $valor): ?Carbon
    {
        if (! is_string($valor) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $valor, config('portal.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }
}
