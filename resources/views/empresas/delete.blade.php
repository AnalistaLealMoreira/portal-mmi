@extends('layouts.app')
@section('title', 'Excluir empresa')
@section('titulo', 'Excluir empresa')
@section('content')
<p>Excluir <strong>{{ $empresa->nome }}</strong>?</p>
<form method="post" action="{{ route('empresas.destroy', $empresa) }}">
    @csrf
    <button type="submit" class="btn btn-danger">Confirmar exclusão</button>
    <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
</form>
@endsection
