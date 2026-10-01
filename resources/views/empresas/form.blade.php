@extends('layouts.app')
@section('title', 'Empresa')
@section('titulo', $empresa->exists ? 'Editar empresa' : 'Nova empresa')
@section('content')
<div class="card form-card">
    <div class="card-body">
        <form method="post" action="{{ $empresa->exists ? route('empresas.update', $empresa) : route('empresas.store') }}">
            @csrf
            <x-campo.texto name="nome" label="Nome" :value="$empresa->nome" maxlength="255" required />
            <x-campo.texto name="cnpj" label="CNPJ" :value="$empresa->cnpj" maxlength="18" required />
            <x-campo.status name="ativo" label="Status" ativado="Ativada" desativado="Não ativada"
                            :value="$empresa->exists ? $empresa->ativo : true" />
            <div class="form-acoes">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
