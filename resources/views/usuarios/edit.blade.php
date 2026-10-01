@extends('layouts.app')
@section('title', 'Editar usuário')
@section('titulo', 'Editar '.$funcionario->usuario->nome_exibicao)
@section('content')
@php($usuario = $funcionario->usuario)
<div class="card form-card">
    <div class="card-body">
        <form method="post" action="{{ route('usuarios.update', $funcionario->id) }}">
            @csrf
            <fieldset class="form-secao">
                <legend>Dados pessoais</legend>
                <div class="form-grid">
                    <x-campo.texto name="first_name" label="Nome" :value="$usuario->first_name" maxlength="150" required />
                    <x-campo.texto name="last_name" label="Sobrenome" :value="$usuario->last_name" maxlength="150" />
                    <x-campo.texto name="email" type="email" label="E-mail" :value="$usuario->email" maxlength="254" class="form-grid-full" />
                </div>
            </fieldset>
            <fieldset class="form-secao">
                <legend>Acesso</legend>
                <div class="form-grid">
                    <div class="mb-3">
                        <label class="form-label" for="id_username">Usuário (login)</label>
                        <input type="text" id="id_username" class="form-control" value="{{ $usuario->username }}" disabled>
                    </div>
                    <x-campo.texto name="password" type="password" label="Nova senha" autocomplete="new-password"
                                   help="Deixe em branco para manter a senha atual." />
            <x-campo.select name="role" label="Nível" required :value="$usuario->role"
                            :opcoes="collect($roles)->map(fn ($rotulo, $valor) => ['valor' => $valor, 'rotulo' => $rotulo])->values()" />
            <x-campo.select name="setor_id" label="Setor" required :value="$funcionario->setor_id"
                            :opcoes="$setores->map(fn ($s) => ['valor' => $s->id, 'rotulo' => $s->nome])" />
                </div>
            </fieldset>
            <div class="form-acoes">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
