<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\LinkBi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(): View
    {
        return view('empresas.index', ['empresas' => Empresa::withCount(['setores', 'funcionarios'])->orderBy('nome')->paginate(25)]);
    }

    /** Hub da empresa: acesso a Setores, Usuários e Links dela. */
    public function show(Request $request, int $empresa): View
    {
        $usuario = $request->user();
        $empresa = Empresa::query()
            ->when(! $usuario->isAdmin(), fn ($q) => $q->whereKey($usuario->empresaId() ?? 0))
            ->findOrFail($empresa);

        if ($usuario->isAdmin()) {
            $request->session()->put('empresa_ativa_id', $empresa->id);
        }

        return view('empresas.show', [
            'empresa' => $empresa,
            'totalSetores' => $empresa->setores()->count(),
            'totalFuncionarios' => $empresa->funcionarios()->count(),
            'totalLinks' => LinkBi::daEmpresa($empresa)->count(),
        ]);
    }

    public function create(): View
    {
        return view('empresas.form', ['empresa' => new Empresa]);
    }

    public function store(Request $request): RedirectResponse
    {
        Empresa::create($this->validar($request));

        return redirect()->route('empresas.index');
    }

    public function edit(Empresa $empresa): View
    {
        return view('empresas.form', ['empresa' => $empresa]);
    }

    public function update(Request $request, Empresa $empresa): RedirectResponse
    {
        $empresa->update($this->validar($request, $empresa));

        return redirect()->route('empresas.index');
    }

    public function confirmDelete(Empresa $empresa): View
    {
        return view('empresas.delete', ['empresa' => $empresa]);
    }

    /** Setores e funcionários protegem a empresa contra exclusão (on_delete=PROTECT no Django). */
    public function destroy(Request $request, Empresa $empresa): RedirectResponse
    {
        $vinculados = $empresa->setores()->orderBy('nome')->limit(5)->pluck('nome')
            ->merge($empresa->funcionarios()->with('usuario')->limit(5)->get()->map(fn ($f) => $f->usuario->nome_exibicao))
            ->take(5);

        if ($vinculados->isNotEmpty()) {
            return redirect()->route('empresas.delete', $empresa)->with(
                'error',
                "Não é possível excluir \"{$empresa->nome}\": ainda existem registros vinculados a ele ({$vinculados->implode(', ')}). Remova-os primeiro."
            );
        }

        $empresa->delete();
        if ((int) $request->session()->get('empresa_ativa_id') === $empresa->id) {
            $request->session()->forget('empresa_ativa_id');
        }

        return redirect()->route('empresas.index');
    }

    private function validar(Request $request, ?Empresa $empresa = null): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:18', Rule::unique(Empresa::class, 'cnpj')->ignore($empresa?->id)],
            'ativo' => ['required', 'boolean'],
        ], [], ['cnpj' => 'CNPJ', 'ativo' => 'status']);
    }
}
