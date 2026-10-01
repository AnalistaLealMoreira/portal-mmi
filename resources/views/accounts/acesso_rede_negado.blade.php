@extends('layouts.app')
@section('title', 'Acesso restrito')
@section('content')
<div class="py-5 text-center">
    <i class="bi bi-shield-lock display-4 text-danger"></i>
    <h1 class="h4 mt-3">Acesso restrito à rede da empresa</h1>
    <p class="text-muted">Este usuário só pode acessar o Portal MMI a partir de uma rede autorizada.</p>
    <form action="{{ route('logout') }}" method="post">
        @csrf
        <button type="submit" class="btn btn-outline-secondary">Sair</button>
    </form>
</div>
@endsection
