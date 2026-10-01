@extends('layouts.app')
@section('title', 'Redes permitidas')
@section('content')
<x-cabecalho descricao="Usuários normais só acessam o portal a partir destas redes ativas.">
    <a href="{{ route('redes.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nova rede</a>
</x-cabecalho>
<div class="table-responsive">
    <table class="table table-hover align-middle bg-white shadow-sm rounded">
        <thead><tr><th>IP ou rede</th><th>Descrição</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($redes as $rede)
        <tr>
            <td><code>{{ $rede->rede }}</code></td>
            <td>{{ $rede->descricao ?: '-' }}</td>
            <td>@if ($rede->ativo)<span class="badge text-bg-success">Ativa</span>@else<span class="badge text-bg-secondary">Inativa</span>@endif</td>
            <td class="text-end">
                <x-acoes :nome="$rede->rede" :editar="route('redes.edit', $rede)"
                         :excluir="route('redes.delete', $rede)" :destroy="route('redes.destroy', $rede)"
                         aviso="Usuários normais deixarão de acessar o portal a partir desta rede." />
            </td>
        </tr>
        @empty
        <tr><td colspan="4"><x-vazio icone="bi-shield-lock" titulo="Nenhuma rede cadastrada" acao="Nova rede" :acao-url="route('redes.create')">Enquanto não houver uma rede ativa, usuários normais ficam bloqueados.</x-vazio></td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
