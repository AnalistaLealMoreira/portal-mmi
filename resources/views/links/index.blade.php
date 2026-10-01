@extends('layouts.app')
@section('title', 'Relatórios')
@section('content')
<x-cabecalho descricao="Relatórios de BI liberados para você, organizados por setor.">
    <x-busca placeholder="Buscar relatório" :manter="['setor']" />
    @include('partials.filtro_setor')
</x-cabecalho>

@if ($recentes->isNotEmpty())
<section class="rel-secao">
    <h2 class="rel-secao-titulo"><i class="bi bi-clock-history"></i> Acessados recentemente</h2>
    <div class="rel-grid rel-grid-recentes">
        @foreach ($recentes as $link)
        <a href="{{ route('links.acessar', $link->id) }}" class="rel-card rel-card-compacto">
            <span class="rel-card-icone"><i class="bi bi-bar-chart-line"></i></span>
            <span class="rel-card-corpo">
                <span class="rel-card-nome">{{ $link->nome }}</span>
                <span class="rel-card-meta">{{ $link->setor->nome }} · {{ data_local($link->ultimo_acesso) }}</span>
            </span>
        </a>
        @endforeach
    </div>
</section>
@endif

@forelse ($linksPorSetor as $setor => $linksDoSetor)
<section class="rel-secao">
    <h2 class="rel-secao-titulo"><i class="bi bi-diagram-3"></i> {{ $setor }} <span class="rel-secao-total">{{ $linksDoSetor->count() }}</span></h2>
    <div class="rel-grid">
        @foreach ($linksDoSetor as $link)
        <a href="{{ route('links.acessar', $link->id) }}" class="rel-card">
            <span class="rel-card-topo">
                <span class="rel-card-icone"><i class="bi bi-bar-chart-line"></i></span>
                <i class="bi bi-arrow-up-right rel-card-seta" aria-hidden="true"></i>
            </span>
            <span class="rel-card-nome">{{ $link->nome }}</span>
            <span class="rel-card-desc">{{ $link->descricao ?: 'Sem descrição.' }}</span>
        </a>
        @endforeach
    </div>
</section>
@empty
@if (request()->filled('q'))
<x-vazio icone="bi-search" titulo="Nenhum relatório encontrado">Nenhum resultado para "{{ request('q') }}".</x-vazio>
@else
<x-vazio icone="bi-bar-chart-line" titulo="Nenhum relatório disponível">
    Ainda não há relatórios liberados para você. Fale com o administrador da sua empresa para pedir acesso.
</x-vazio>
@endif
@endforelse

{{ $links->links() }}
@endsection
