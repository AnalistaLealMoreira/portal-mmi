@extends('layouts.app')
@section('title', 'Setores')
@section('titulo', 'Setores de '.$empresa->nome)
@section('content')
@include('partials.breadcrumb_empresa', ['atual' => 'Setores'])
<x-cabecalho descricao="Áreas da empresa. Cada usuário e cada link de BI pertence a um setor.">
    <a href="{{ route('setores.create', $empresa) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Novo setor</a>
</x-cabecalho>
<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead><tr><th>Nome</th><th></th></tr></thead>
        <tbody>
        @forelse ($setores as $setor)
        <tr>
            <td>{{ $setor->nome }}</td>
            <td class="text-end">
                <x-acoes :nome="$setor->nome" :editar="route('setores.edit', [$empresa, $setor->id])"
                         :excluir="route('setores.delete', [$empresa, $setor->id])" :destroy="route('setores.destroy', [$empresa, $setor->id])"
                         :aviso="'Também serão excluídos, permanentemente, '.qtd($setor->links_bi_count, 'link de BI', 'links de BI').' (com o histórico de acesso) e '.qtd($setor->funcionarios_count, 'usuário vinculado', 'usuários vinculados').'.'" />
            </td>
        </tr>
        @empty
        <tr><td colspan="2"><x-vazio icone="bi-diagram-3" titulo="Nenhum setor cadastrado" acao="Novo setor" :acao-url="route('setores.create', $empresa)">Crie os setores da empresa (ex.: Financeiro, Comercial) para organizar usuários e links.</x-vazio></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $setores->links() }}
@endsection
