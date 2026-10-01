@extends('layouts.app')
@section('title', 'Excluir setor')
@section('titulo', 'Excluir setor')
@section('content')
<p>Excluir <strong>{{ $setor->nome }}</strong>?</p>
<p class="text-danger">Esta ação também excluirá, permanentemente, todos os links de BI deste setor (com seus históricos de acesso) e todos os usuários vinculados a ele.</p>
<form method="post" action="{{ route('setores.destroy', [$empresa, $setor->id]) }}">
    @csrf
    <button type="submit" class="btn btn-danger">Confirmar exclusão</button>
    <a href="{{ route('setores.index', $empresa) }}" class="btn btn-outline-secondary">Cancelar</a>
</form>
@endsection
