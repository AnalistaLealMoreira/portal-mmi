@extends('layouts.app')
@section('title', 'Excluir rede')
@section('titulo', 'Excluir rede permitida')
@section('content')
<div class="card form-card">
    <div class="card-body">
        <p>Deseja excluir a rede <strong>{{ $rede->rede }}</strong>?</p>
        <form method="post" action="{{ route('redes.destroy', $rede) }}">
            @csrf
            <div class="form-acoes">
                <button type="submit" class="btn btn-danger">Excluir</button>
                <a href="{{ route('redes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
