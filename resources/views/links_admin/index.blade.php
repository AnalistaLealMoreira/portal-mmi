@extends('layouts.app')
@section('title', 'Gerenciar Links de BI')
@section('titulo', 'Links de BI de '.$empresa->nome)
@section('content')
@include('partials.breadcrumb_empresa', ['atual' => 'Links de BI'])
<x-cabecalho descricao="Relatórios de BI cadastrados para a empresa e quem pode acessar cada um.">
    <x-busca placeholder="Buscar link" :manter="['setor']" />
    @include('partials.filtro_setor')
    <a href="{{ route('links-admin.create', $empresa) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Novo link</a>
</x-cabecalho>
<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead><tr><th>Nome</th><th>Setor</th><th>Ativo</th><th></th></tr></thead>
        <tbody>
        @forelse ($links as $link)
        <tr>
            <td>{{ $link->nome }}</td>
            <td>{{ $link->setor->nome }}</td>
            <td>@if ($link->ativo)<span class="badge text-bg-success">Sim</span>@else<span class="badge text-bg-secondary">Não</span>@endif</td>
            <td class="text-end">
                <x-acoes :nome="$link->nome" :editar="route('links-admin.edit', [$empresa, $link->id])"
                         :excluir="route('links-admin.delete', [$empresa, $link->id])" :destroy="route('links-admin.destroy', [$empresa, $link->id])"
                         :aviso="'O histórico de acesso deste link ('.qtd($link->acessos_count, 'registro', 'registros').') também será excluído, permanentemente.'" />
            </td>
        </tr>
        @empty
        <tr><td colspan="4">
            @if (request()->filled('q') || request()->filled('setor'))
            <x-vazio icone="bi-search" titulo="Nenhum link encontrado">Nenhum link corresponde aos filtros aplicados.</x-vazio>
            @else
            <x-vazio icone="bi-link-45deg" titulo="Nenhum link cadastrado" acao="Novo link" :acao-url="route('links-admin.create', $empresa)">Cadastre o primeiro relatório de BI desta empresa e escolha quem pode acessá-lo.</x-vazio>
            @endif
        </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $links->links() }}
@endsection
