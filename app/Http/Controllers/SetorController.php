<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Setor;
use App\Services\ExclusaoEmCascata;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Setores de uma empresa. O acesso à empresa já foi validado pelo middleware `empresa.escopo`. */
class SetorController extends Controller
{
    public function index(Empresa $empresa): View
    {
        return view('setores.index', [
            'empresa' => $empresa,
            'setores' => Setor::daEmpresa($empresa)->withCount(['linksBi', 'funcionarios'])->orderBy('nome')->paginate(25),
        ]);
    }

    public function create(Empresa $empresa): View
    {
        return view('setores.form', ['empresa' => $empresa, 'setor' => new Setor]);
    }

    public function store(Request $request, Empresa $empresa): RedirectResponse
    {
        $empresa->setores()->create($this->validar($request, $empresa));

        return redirect()->route('setores.index', $empresa);
    }

    public function edit(Empresa $empresa, int $setor): View
    {
        return view('setores.form', ['empresa' => $empresa, 'setor' => $this->setor($empresa, $setor)]);
    }

    public function update(Request $request, Empresa $empresa, int $setor): RedirectResponse
    {
        $setor = $this->setor($empresa, $setor);
        $setor->update($this->validar($request, $empresa, $setor));

        return redirect()->route('setores.index', $empresa);
    }

    public function confirmDelete(Empresa $empresa, int $setor): View
    {
        return view('setores.delete', ['empresa' => $empresa, 'setor' => $this->setor($empresa, $setor)]);
    }

    /** Exclui o setor em cascata: links de BI (com logs de acesso) e usuários vinculados. */
    public function destroy(Empresa $empresa, int $setor): RedirectResponse
    {
        $setor = $this->setor($empresa, $setor);
        ExclusaoEmCascata::setor($setor);

        return redirect()->route('setores.index', $empresa)
            ->with('success', "Setor \"{$setor->nome}\" excluído, junto com seus links de BI e usuários vinculados.");
    }

    private function setor(Empresa $empresa, int $id): Setor
    {
        return Setor::daEmpresa($empresa)->findOrFail($id);
    }

    private function validar(Request $request, Empresa $empresa, ?Setor $setor = null): array
    {
        return $request->validate([
            'nome' => [
                'required', 'string', 'max:255',
                Rule::unique(Setor::class, 'nome')->where('empresa_id', $empresa->id)->ignore($setor?->id),
            ],
        ], ['nome.unique' => 'Já existe um setor com este nome nesta empresa.']);
    }
}
