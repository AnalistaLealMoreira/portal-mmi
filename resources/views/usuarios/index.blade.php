@extends('layouts.app')
@section('title', 'Usuários')
@section('content')
@php($isAdmin = auth()->user()->isAdmin())
<x-cabecalho descricao="Pessoas com acesso ao portal, com nível de permissão e setor.">
    <x-busca placeholder="Buscar por nome, login ou e-mail" />
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Novo usuário</a>
</x-cabecalho>
<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead>
            <tr>
                <th>Nome</th>
                @if ($isAdmin)<th>Empresa</th>@endif
                <th>Nível</th>
                <th>Setor</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($funcionarios as $funcionario)
        <tr>
            <td>
                <span class="pessoa">
                    <span class="pessoa-avatar">{{ $funcionario->usuario->iniciais }}</span>
                    <span class="pessoa-info">
                        <span class="pessoa-nome">{{ $funcionario->usuario->nome_exibicao }}</span>
                        @if ($funcionario->usuario->email)<span class="pessoa-email">{{ $funcionario->usuario->email }}</span>@endif
                    </span>
                </span>
            </td>
            @if ($isAdmin)<td>{{ $funcionario->empresa->nome }}</td>@endif
            <td><span class="papel papel-{{ strtolower($funcionario->usuario->role) }}">{{ $funcionario->usuario->role_label }}</span></td>
            <td>{{ $funcionario->setor?->nome ?? '—' }}</td>
            <td class="text-end">
                <x-acoes :nome="$funcionario->usuario->nome_exibicao" :editar="route('usuarios.edit', $funcionario->id)"
                         :excluir="route('usuarios.delete', $funcionario->id)" :destroy="route('usuarios.destroy', $funcionario->id)"
                         :aviso="'Também serão excluídos, permanentemente, '.qtd($funcionario->usuario->links_criados_count, 'link de BI criado', 'links de BI criados').' por este usuário (com os históricos de acesso) e o histórico de acesso dele.'" />
            </td>
        </tr>
        @empty
        <tr><td colspan="5">
            @if (request()->filled('q'))
            <x-vazio icone="bi-search" titulo="Nenhum usuário encontrado">Nenhum resultado para "{{ request('q') }}". Tente outro nome ou e-mail.</x-vazio>
            @else
            <x-vazio icone="bi-people" titulo="Nenhum usuário cadastrado" acao="Novo usuário" :acao-url="route('usuarios.create')">Cadastre as pessoas que vão acessar os relatórios.</x-vazio>
            @endif
        </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $funcionarios->links() }}
@endsection
