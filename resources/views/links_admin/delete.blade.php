@extends('layouts.app')
@section('title', 'Excluir link')
@section('titulo', 'Excluir link')
@section('content')
<p>Excluir <strong>{{ $link->nome }}</strong>?</p>
<p class="text-danger">Esta ação também excluirá, permanentemente, o histórico de acesso registrado para este link.</p>
<form method="post" action="{{ route('links-admin.destroy', [$empresa, $link->id]) }}">
    @csrf
    <button type="submit" class="btn btn-danger">Confirmar exclusão</button>
    <a href="{{ route('links-admin.index', $empresa) }}" class="btn btn-outline-secondary">Cancelar</a>
</form>
@endsection
