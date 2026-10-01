@extends('layouts.app')
@section('title', 'Empresas')
@section('content')
<x-cabecalho descricao="Empresas atendidas pelo portal. Clique no nome para ver setores, usuários e links.">
    <a href="{{ route('empresas.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nova empresa</a>
</x-cabecalho>
<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead><tr><th>Nome</th><th>CNPJ</th><th>Ativo</th><th></th></tr></thead>
        <tbody>
        @forelse ($empresas as $empresa)
        <tr>
            <td><a href="{{ route('empresas.show', $empresa) }}">{{ $empresa->nome }}</a></td>
            <td>{{ $empresa->cnpj }}</td>
            <td>@if ($empresa->ativo)<span class="badge text-bg-success">Sim</span>@else<span class="badge text-bg-secondary">Não</span>@endif</td>
            <td class="text-end">
                @php($vinculos = $empresa->setores_count + $empresa->funcionarios_count)
                <x-acoes :nome="$empresa->nome" :editar="route('empresas.edit', $empresa)"
                         :excluir="route('empresas.delete', $empresa)" :destroy="route('empresas.destroy', $empresa)"
                         :bloqueado="$vinculos > 0"
                         :aviso="$vinculos > 0
                            ? 'Não é possível excluir: a empresa ainda tem '.qtd($empresa->setores_count, 'setor', 'setores').' e '.qtd($empresa->funcionarios_count, 'usuário', 'usuários').'. Remova-os primeiro.'
                            : 'A empresa não tem setores nem usuários. Esta ação não pode ser desfeita.'" />
            </td>
        </tr>
        @empty
        <tr><td colspan="4"><x-vazio icone="bi-building" titulo="Nenhuma empresa cadastrada" acao="Nova empresa" :acao-url="route('empresas.create')">Cadastre a primeira empresa para depois criar setores, usuários e links.</x-vazio></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $empresas->links() }}
@endsection
