@extends('layouts.app')
@section('title', 'Excluir usuário')
@section('titulo', 'Excluir usuário')
@section('content')
<p>Excluir <strong>{{ $funcionario->usuario->nome_exibicao }}</strong>?</p>
<p class="text-danger">Esta ação também excluirá, permanentemente, todos os links de BI criados por este usuário (com seus históricos de acesso) e o histórico de acesso do próprio usuário.</p>
<form method="post" action="{{ route('usuarios.destroy', $funcionario->id) }}">
    @csrf
    <button type="submit" class="btn btn-danger">Confirmar exclusão</button>
    <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
</form>
@endsection
