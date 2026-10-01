@extends('layouts.app')
@section('title', 'Link de BI')
@section('titulo', ($link->exists ? 'Editar' : 'Novo').' link de BI em '.$empresa->nome)
@section('content')
<div class="card form-card">
    <div class="card-body">
        <form method="post" action="{{ $link->exists ? route('links-admin.update', [$empresa, $link->id]) : route('links-admin.store', $empresa) }}">
            @csrf
            <fieldset class="form-secao">
                <legend>Relatório</legend>
            <x-campo.select name="setor_id" label="Setor" :value="$link->setor_id" required
                            :opcoes="$setores->map(fn ($s) => ['valor' => $s->id, 'rotulo' => $s->nome])" />
            <x-campo.texto name="nome" label="Nome" :value="$link->nome" maxlength="255" required />
            <x-campo.texto name="url" type="url" label="URL" :value="$link->url" maxlength="500" required />
            <x-campo.status name="ativo" label="Situação" ativado="Ativado" desativado="Desativado"
                            :value="$link->exists ? $link->ativo : true" />
            </fieldset>
            <fieldset class="form-secao">
                <legend>Acesso</legend>
            <x-campo.checklist name="usuarios_liberados" label="Quem pode acessar" :selecionados="$liberadosAtuais"
                               :opcoes="$funcionarios->map(fn ($f) => ['valor' => $f->id, 'rotulo' => $f->rotulo_com_setor])" />
            </fieldset>
            <div class="form-acoes">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('links-admin.index', $empresa) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
