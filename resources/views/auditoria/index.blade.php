@extends('layouts.app')
@section('title', 'Auditoria de Acessos')
@section('content')
<x-cabecalho descricao="Cada abertura de relatório fica registrada com usuário, data, hora e IP." />
<form method="get" class="filtros">
    <div class="busca">
        <i class="bi bi-search busca-icone" aria-hidden="true"></i>
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm busca-campo"
               placeholder="Usuário ou relatório" aria-label="Buscar por usuário ou relatório">
    </div>
    <label class="filtros-data">De <input type="date" name="de" value="{{ request('de') }}" class="form-control form-control-sm"></label>
    <label class="filtros-data">Até <input type="date" name="ate" value="{{ request('ate') }}" class="form-control form-control-sm"></label>
    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
    @if (request()->hasAny(['q', 'de', 'ate', 'funcionario', 'link']))
    <a href="{{ route('auditoria.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
    @endif
</form>
<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead><tr><th>Usuário</th><th>Link</th><th>Setor</th><th>Data/Hora</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($acessos as $acesso)
        <tr>
            <td>{{ $acesso->usuario->nome_exibicao }}</td>
            <td>{{ $acesso->link->nome }}</td>
            <td>{{ $acesso->link->setor->nome }}</td>
            <td>{{ data_local($acesso->acessado_em, 'd/m/Y H:i') }}</td>
            <td>{{ $acesso->ip_address }}</td>
        </tr>
        @empty
        <tr><td colspan="5">
            <x-vazio icone="bi-shield-check" titulo="{{ request()->hasAny(['q', 'de', 'ate']) ? 'Nenhum acesso encontrado' : 'Nenhum acesso registrado' }}">
                {{ request()->hasAny(['q', 'de', 'ate']) ? 'Ajuste a busca ou o período.' : 'Os acessos aparecem aqui assim que alguém abrir um relatório.' }}
            </x-vazio>
        </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $acessos->links() }}
@endsection
