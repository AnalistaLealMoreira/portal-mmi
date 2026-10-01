@extends('layouts.app')
@section('title', $empresa->nome)
@section('titulo', $empresa->nome)
@section('content')
<x-cabecalho :descricao="'CNPJ '.$empresa->cnpj.' · '.($empresa->ativo ? 'Ativa' : 'Inativa')" />

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="{{ route('setores.index', $empresa) }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="kpi-icon mx-auto mb-2"><i class="bi bi-diagram-3"></i></div>
                    <div class="fs-3 fw-bold">{{ $totalSetores }}</div>
                    <div class="fw-semibold text-body">Setores</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="kpi-icon mx-auto mb-2"><i class="bi bi-people"></i></div>
                <div class="fs-3 fw-bold">{{ $totalFuncionarios }}</div>
                <div class="fw-semibold text-body">Usuários cadastrados</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <a href="{{ route('links-admin.index', $empresa) }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="kpi-icon mx-auto mb-2"><i class="bi bi-link-45deg"></i></div>
                    <div class="fs-3 fw-bold">{{ $totalLinks }}</div>
                    <div class="fw-semibold text-body">Links de BI</div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="d-flex gap-2">
    <a href="{{ route('setores.create', $empresa) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Cadastrar setor</a>
    <a href="{{ route('usuarios.create', ['empresa_id_lock' => $empresa->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Cadastrar usuário</a>
    @if (auth()->user()->isAdmin())
    <a href="{{ route('empresas.edit', $empresa) }}" class="btn btn-outline-primary btn-sm">Editar empresa</a>
    <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    @else
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    @endif
</div>
@endsection
