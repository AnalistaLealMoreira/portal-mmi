@extends('layouts.app')
@section('title', 'Cadastrar usuário')
@section('titulo', $empresaLocked ? 'Cadastrar usuário em '.$empresaLocked->nome : 'Cadastrar novo usuário')
@section('content')
<div class="card form-card">
    <div class="card-body">
        <form method="post" action="{{ route('usuarios.store') }}">
            @csrf
            @if ($empresaLocked)
            <input type="hidden" name="empresa_id_lock" value="{{ $empresaLocked->id }}">
            @endif
            <fieldset class="form-secao">
                <legend>Dados pessoais</legend>
                <div class="form-grid">
                    <x-campo.texto name="first_name" label="Nome" maxlength="150" required />
                    <x-campo.texto name="last_name" label="Sobrenome" maxlength="150" />
                    <x-campo.texto name="email" type="email" label="E-mail" maxlength="254" class="form-grid-full" />
                </div>
            </fieldset>
            <fieldset class="form-secao">
                <legend>Acesso</legend>
                <div class="form-grid">
                    <x-campo.texto name="username" label="Usuário (login)" readonly autocomplete="username"
                                   help="Gerado a partir do texto antes do @ no e-mail." />
                    <x-campo.texto name="password" type="password" label="Senha" required autocomplete="new-password" />
            @unless ($empresaFixa)
            <x-campo.select name="empresa_id" label="Empresa" required
                            :opcoes="$empresas->map(fn ($e) => ['valor' => $e->id, 'rotulo' => $e->nome])" />
            @endunless
            <x-campo.select name="role" label="Nível" required value="{{ \App\Models\Usuario::NORMAL }}"
                            :opcoes="collect($roles)->map(fn ($rotulo, $valor) => ['valor' => $valor, 'rotulo' => $rotulo])->values()" />
            <x-campo.select name="setor_id" label="Setor" required
                            :opcoes="$setores->map(fn ($s) => $empresaFixa
                                ? ['valor' => $s->id, 'rotulo' => $s->nome]
                                : ['valor' => $s->id, 'rotulo' => $s->nome.' ('.$s->empresa->nome.')', 'attrs' => ['data-empresa-id' => $s->empresa_id]])" />
                </div>
            </fieldset>
            <div class="form-acoes">
                <button type="submit" class="btn btn-primary">Cadastrar</button>
                @if ($empresaLocked)
                <a href="{{ route('empresas.show', $empresaLocked) }}" class="btn btn-outline-secondary">Cancelar</a>
                @else
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const email = document.getElementById("id_email");
        const username = document.getElementById("id_username");
        const empresa = document.getElementById("id_empresa_id");
        const setor = document.getElementById("id_setor_id");
        if (!email || !username) return;

        email.addEventListener("input", function () {
            username.value = email.value
                .split("@", 1)[0]
                .trim()
                .replace(/[._-]+/g, " ")
                .replace(/\s+/g, " ")
                .toLowerCase()
                .replace(/(^|\s)\S/g, function (letra) { return letra.toUpperCase(); });
        });

        if (empresa && setor) {
            const opcoesSetor = Array.from(setor.options);
            function filtrarSetores(limpar) {
                const empresaSelecionada = empresa.value;
                if (limpar) setor.value = "";
                opcoesSetor.forEach(function (opcao) {
                    opcao.hidden = Boolean(opcao.value) && opcao.dataset.empresaId !== empresaSelecionada;
                });
            }
            empresa.addEventListener("change", function () { filtrarSetores(true); });
            filtrarSetores(false);
        }
    })();
</script>
@endpush
