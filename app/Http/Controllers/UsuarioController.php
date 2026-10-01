<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\Setor;
use App\Models\Usuario;
use App\Services\ExclusaoEmCascata;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Normalizer;

/**
 * Cadastro de usuários: cria o login (Usuario) e o vínculo (Funcionario) com
 * empresa, setor e nível. Admin global vê e gerencia todas as empresas;
 * Admin de Empresa fica restrito à própria.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('q', ''));
        $funcionarios = Funcionario::gerenciaveisPor($request->user())
            ->when($busca !== '', fn ($q) => $q->whereHas('usuario', fn ($u) => $u->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$busca}%")
                ->orWhere('last_name', 'like', "%{$busca}%")
                ->orWhere('username', 'like', "%{$busca}%")
                ->orWhere('email', 'like', "%{$busca}%"))))
            ->with(['usuario' => fn ($q) => $q->withCount('linksCriados'), 'empresa', 'setor'])
            ->ordenado()
            ->paginate(25)
            ->withQueryString();

        return view('usuarios.index', ['funcionarios' => $funcionarios]);
    }

    /**
     * Admin global escolhe a empresa no formulário, ou ela já vem travada
     * quando chega pelo hub da empresa (?empresa_id_lock=). Admin de Empresa
     * fica sempre travado na própria empresa.
     */
    public function create(Request $request): View
    {
        $empresaFixa = $this->empresaFixa($request);

        return view('usuarios.cadastro', [
            'empresaFixa' => $empresaFixa,
            'empresaLocked' => $request->user()->isAdmin() ? $empresaFixa : null,
            'empresas' => $empresaFixa ? collect() : Empresa::orderBy('nome')->get(),
            'setores' => $empresaFixa
                ? Setor::daEmpresa($empresaFixa)->orderBy('nome')->get()
                : Setor::with('empresa')->get()->sortBy(fn ($s) => [$s->empresa->nome, $s->nome])->values(),
            'roles' => Usuario::ROLES_GERENCIAVEIS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaFixa = $this->empresaFixa($request);
        $request->merge(['username' => self::usernameDoEmail((string) $request->input('email')) ?: trim((string) $request->input('username'))]);

        $validator = validator($request->all(), [
            'username' => ['required', 'string', 'max:150'],
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:254'],
            'password' => ['required', 'string'],
            'role' => ['required', Rule::in(array_keys(Usuario::ROLES_GERENCIAVEIS))],
            'empresa_id' => $empresaFixa ? ['nullable'] : ['required', Rule::exists(Empresa::class, 'id')],
            'setor_id' => ['required', Rule::exists(Setor::class, 'id')],
        ], [
            'username.required' => 'Informe um e-mail válido para gerar o login.',
        ], $this->atributos());

        $validator->after(function (Validator $validator) use ($request, $empresaFixa) {
            $username = $request->input('username');
            if ($username && Usuario::whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->exists()) {
                $validator->errors()->add('username', 'Já existe um usuário com esse login.');
            }
            $empresaId = $empresaFixa?->id ?? (int) $request->input('empresa_id');
            $setor = Setor::find($request->input('setor_id'));
            if ($setor && $empresaId && $setor->empresa_id !== $empresaId) {
                $validator->errors()->add('setor_id', $empresaFixa
                    ? 'Faça uma escolha válida. Sua escolha não é uma das disponíveis.'
                    : 'O setor deve pertencer à empresa selecionada.');
            }
        });

        $dados = $validator->validate();
        $empresaId = $empresaFixa?->id ?? (int) $dados['empresa_id'];

        $funcionario = DB::transaction(function () use ($dados, $empresaId) {
            $usuario = Usuario::create([
                'username' => $dados['username'],
                'first_name' => $dados['first_name'],
                'last_name' => $dados['last_name'] ?? '',
                'email' => $dados['email'] ?? '',
                'role' => $dados['role'],
                'password' => $dados['password'],
            ]);

            return Funcionario::create([
                'usuario_id' => $usuario->id,
                'empresa_id' => $empresaId,
                'setor_id' => (int) $dados['setor_id'],
            ]);
        });

        $destino = $request->user()->isAdmin() && $empresaFixa && $empresaFixa->id === $funcionario->empresa_id
            ? route('empresas.show', $empresaFixa)
            : route('usuarios.index');

        return redirect($destino)->with('success', "Usuário \"{$funcionario->usuario->nome_exibicao}\" cadastrado com sucesso.");
    }

    public function edit(Request $request, int $funcionario): View
    {
        $funcionario = $this->funcionario($request, $funcionario);

        return view('usuarios.edit', [
            'funcionario' => $funcionario,
            'setores' => Setor::daEmpresa($funcionario->empresa_id)->orderBy('nome')->get(),
            'roles' => Usuario::ROLES_GERENCIAVEIS,
        ]);
    }

    /**
     * Login travado (não editável), senha opcional e empresa fixa: evita
     * reatribuir alguém para outra empresa sem passar pelo cadastro.
     */
    public function update(Request $request, int $funcionario): RedirectResponse
    {
        $funcionario = $this->funcionario($request, $funcionario);

        $dados = $request->validate([
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:254'],
            'password' => ['nullable', 'string'],
            'role' => ['required', Rule::in(array_keys(Usuario::ROLES_GERENCIAVEIS))],
            'setor_id' => ['required', Rule::exists(Setor::class, 'id')->where('empresa_id', $funcionario->empresa_id)],
        ], ['setor_id.exists' => 'Faça uma escolha válida. Sua escolha não é uma das disponíveis.'], $this->atributos());

        DB::transaction(function () use ($funcionario, $dados) {
            $funcionario->update(['setor_id' => (int) $dados['setor_id']]);

            $usuario = $funcionario->usuario;
            $usuario->fill([
                'first_name' => $dados['first_name'],
                'last_name' => $dados['last_name'] ?? '',
                'email' => $dados['email'] ?? '',
                'role' => $dados['role'],
            ]);
            if (! empty($dados['password'])) {
                $usuario->password = $dados['password'];
            }
            $usuario->save();
        });

        return redirect()->route('usuarios.index');
    }

    public function confirmDelete(Request $request, int $funcionario): View
    {
        return view('usuarios.delete', ['funcionario' => $this->funcionario($request, $funcionario)]);
    }

    /** Exclui o usuário em cascata: links que criou (com logs) e o próprio histórico de acesso. */
    public function destroy(Request $request, int $funcionario): RedirectResponse
    {
        $usuario = $this->funcionario($request, $funcionario)->usuario;
        $nome = $usuario->nome_exibicao;
        ExclusaoEmCascata::usuario($usuario);

        return redirect()->route('usuarios.index')
            ->with('success', "Usuário \"{$nome}\" excluído, junto com os links que criou e seu histórico de acesso.");
    }

    /** Restringe ao que o usuário logado pode gerenciar (fecha o IDOR por URL). */
    private function funcionario(Request $request, int $id): Funcionario
    {
        return Funcionario::gerenciaveisPor($request->user())->with(['usuario', 'empresa'])->findOrFail($id);
    }

    private function empresaFixa(Request $request): ?Empresa
    {
        $usuario = $request->user();
        if (! $usuario->isAdmin()) {
            return $usuario->funcionario?->empresa ?? abort(403);
        }
        $lock = $request->input('empresa_id_lock');

        return $lock ? Empresa::find($lock) : null;
    }

    private function atributos(): array
    {
        return [
            'username' => 'usuário (login)', 'first_name' => 'nome', 'last_name' => 'sobrenome',
            'email' => 'e-mail', 'password' => 'senha', 'role' => 'nível',
            'empresa_id' => 'empresa', 'setor_id' => 'setor',
        ];
    }

    /**
     * Gera o login a partir do texto antes do @ do e-mail, igual ao portal
     * Django: "joao.silva@x.com" vira "Joao Silva".
     */
    public static function usernameDoEmail(string $email): string
    {
        if (! str_contains($email, '@')) {
            return '';
        }
        $local = mb_strtolower(trim(explode('@', $email, 2)[0]));
        $ascii = preg_replace('/[^\x00-\x7F]/', '', Normalizer::normalize($local, Normalizer::FORM_KD) ?: $local);
        $username = preg_replace('/[^a-z0-9._-]+/', ' ', $ascii);
        $username = trim(preg_replace('/[._-]+/', ' ', $username));

        // str.title() do Python: maiúscula em toda letra que não vem depois de outra letra.
        return preg_replace_callback('/(?<![a-z])[a-z]/', fn ($m) => strtoupper($m[0]), $username);
    }
}
