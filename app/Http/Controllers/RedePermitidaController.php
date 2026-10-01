<?php

namespace App\Http\Controllers;

use App\Models\RedePermitida;
use App\Support\Cidr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** CRUD das redes autorizadas para usuários normais (somente Administrador). */
class RedePermitidaController extends Controller
{
    public function index(): View
    {
        return view('redes.index', ['redes' => RedePermitida::orderBy('rede')->get()]);
    }

    public function create(): View
    {
        return view('redes.form', ['rede' => new RedePermitida]);
    }

    public function store(Request $request): RedirectResponse
    {
        RedePermitida::create($this->validar($request));

        return redirect()->route('redes.index');
    }

    public function edit(RedePermitida $rede): View
    {
        return view('redes.form', ['rede' => $rede]);
    }

    public function update(Request $request, RedePermitida $rede): RedirectResponse
    {
        $rede->update($this->validar($request, $rede));

        return redirect()->route('redes.index');
    }

    public function confirmDelete(RedePermitida $rede): View
    {
        return view('redes.delete', ['rede' => $rede]);
    }

    public function destroy(RedePermitida $rede): RedirectResponse
    {
        $rede->delete();

        return redirect()->route('redes.index');
    }

    /** Normaliza o CIDR antes de validar a unicidade (ex.: 192.168.1.10/24 vira 192.168.1.0/24). */
    private function validar(Request $request, ?RedePermitida $rede = null): array
    {
        $normalizada = Cidr::normalizar((string) $request->input('rede'));
        if ($normalizada !== null) {
            $request->merge(['rede' => $normalizada]);
        }

        $dados = $request->validate([
            'rede' => [
                'required', 'string', 'max:43',
                function ($atributo, $valor, $falha) use ($normalizada) {
                    if ($normalizada === null) {
                        $falha('Informe um IP ou uma rede válida em formato CIDR.');
                    }
                },
                Rule::unique(RedePermitida::class, 'rede')->ignore($rede?->id),
            ],
            'descricao' => ['nullable', 'string', 'max:120'],
        ], [], ['rede' => 'IP ou rede (CIDR)', 'descricao' => 'descrição']);

        return [
            'rede' => $dados['rede'],
            'descricao' => $dados['descricao'] ?? '',
            'ativo' => $request->boolean('ativo'),
        ];
    }
}
