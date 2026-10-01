<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\LinkBi;
use App\Models\Setor;
use App\Models\Usuario;
use App\Services\ExclusaoEmCascata;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/** Gerenciador de links de BI de uma empresa (Admin ou Admin da própria empresa). */
class LinkBiAdminController extends Controller
{
    public function index(Request $request, Empresa $empresa): View
    {
        $setorId = $request->query('setor', '');

        $links = LinkBi::daEmpresa($empresa)
            ->with('setor')
            ->when($setorId !== '', fn ($q) => $q->where('bi_links_linkbi.setor_id', $setorId))
            ->when(trim((string) $request->query('q', '')) !== '', fn ($q) => $q->where('bi_links_linkbi.nome', 'like', '%'.trim($request->query('q')).'%'))
            ->ordenado()
            ->withCount('acessos')
            ->paginate(25)
            ->withQueryString();

        return view('links_admin.index', [
            'empresa' => $empresa,
            'links' => $links,
            'setores' => Setor::daEmpresa($empresa)->orderBy('nome')->get(),
            'setorSelecionado' => (string) $setorId,
        ]);
    }

    public function create(Empresa $empresa): View
    {
        return view('links_admin.form', $this->dadosFormulario($empresa, new LinkBi));
    }

    public function store(Request $request, Empresa $empresa): RedirectResponse
    {
        $dados = $this->validar($request, $empresa);

        DB::transaction(function () use ($dados, $request) {
            $link = LinkBi::create($dados['link'] + ['criado_por_id' => $request->user()->id]);
            $link->funcionariosLiberados()->sync($dados['liberados']);
        });

        return redirect()->route('links-admin.index', $empresa);
    }

    public function edit(Empresa $empresa, int $link): View
    {
        return view('links_admin.form', $this->dadosFormulario($empresa, $this->link($empresa, $link)));
    }

    public function update(Request $request, Empresa $empresa, int $link): RedirectResponse
    {
        $link = $this->link($empresa, $link);
        $dados = $this->validar($request, $empresa);

        DB::transaction(function () use ($link, $dados) {
            $link->update($dados['link']);
            $link->funcionariosLiberados()->sync($dados['liberados']);
        });

        return redirect()->route('links-admin.index', $empresa);
    }

    public function confirmDelete(Empresa $empresa, int $link): View
    {
        return view('links_admin.delete', ['empresa' => $empresa, 'link' => $this->link($empresa, $link)]);
    }

    /** Exclui o link e o histórico de acesso (AcessoLog) vinculado a ele. */
    public function destroy(Empresa $empresa, int $link): RedirectResponse
    {
        $link = $this->link($empresa, $link);
        ExclusaoEmCascata::link($link);

        return redirect()->route('links-admin.index', $empresa)
            ->with('success', "Link \"{$link->nome}\" excluído, junto com seu histórico de acesso.");
    }

    private function link(Empresa $empresa, int $id): LinkBi
    {
        return LinkBi::daEmpresa($empresa)->findOrFail($id);
    }

    private function dadosFormulario(Empresa $empresa, LinkBi $link): array
    {
        return [
            'empresa' => $empresa,
            'link' => $link,
            'setores' => Setor::daEmpresa($empresa)->orderBy('nome')->get(),
            'funcionarios' => Funcionario::where('funcionarios_funcionario.empresa_id', $empresa->id)
                ->with(['usuario', 'setor'])->ordenado()->get(),
            'liberadosAtuais' => $link->exists ? $link->funcionariosLiberados()->pluck('funcionarios_funcionario.id')->all() : [],
        ];
    }

    /**
     * A liberação individual só vale para usuários do setor do link; usuários
     * especiais podem receber links de qualquer setor da empresa.
     */
    private function validar(Request $request, Empresa $empresa): array
    {
        $validator = validator($request->all(), [
            'setor_id' => ['required', Rule::exists(Setor::class, 'id')->where('empresa_id', $empresa->id)],
            'nome' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'ativo' => ['required', 'boolean'],
            'usuarios_liberados' => ['array'],
            'usuarios_liberados.*' => ['integer', Rule::exists(Funcionario::class, 'id')->where('empresa_id', $empresa->id)],
        ], [
            'setor_id.exists' => 'Faça uma escolha válida. Sua escolha não é uma das disponíveis.',
            'usuarios_liberados.*.exists' => 'Faça uma escolha válida. Sua escolha não é uma das disponíveis.',
        ], ['setor_id' => 'setor', 'url' => 'URL', 'ativo' => 'situação', 'usuarios_liberados' => 'quem pode acessar']);

        $validator->after(function (Validator $validator) use ($request) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $foraDoSetor = Funcionario::whereIn('funcionarios_funcionario.id', $request->input('usuarios_liberados', []))
                ->where(fn ($q) => $q->whereNull('setor_id')->orWhere('setor_id', '!=', $request->input('setor_id')))
                ->whereHas('usuario', fn ($q) => $q->where('role', '!=', Usuario::ESPECIAL))
                ->exists();
            if ($foraDoSetor) {
                $validator->errors()->add('usuarios_liberados', 'Só é possível liberar para usuários do setor escolhido.');
            }
        });

        $dados = $validator->validate();

        return [
            'link' => [
                'setor_id' => (int) $dados['setor_id'],
                'nome' => $dados['nome'],
                'url' => $dados['url'],
                'ativo' => (bool) $dados['ativo'],
            ],
            'liberados' => array_map('intval', $dados['usuarios_liberados'] ?? []),
        ];
    }
}
