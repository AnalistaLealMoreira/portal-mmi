@extends('layouts.app')
@section('title', 'Setor')
@section('titulo', ($setor->exists ? 'Editar' : 'Novo').' setor em '.$empresa->nome)
@section('content')
@include('partials.breadcrumb_empresa', [
    'trilha' => ['Setores' => route('setores.index', $empresa)],
    'atual' => $setor->exists ? 'Editar' : 'Novo',
])
<div class="card form-card">
    <div class="card-body">
        <form method="post" action="{{ $setor->exists ? route('setores.update', [$empresa, $setor->id]) : route('setores.store', $empresa) }}">
            @csrf
            <x-campo.texto name="nome" label="Nome" :value="$setor->nome" maxlength="255" required />
            <div class="form-acoes">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('setores.index', $empresa) }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
