@extends('layouts.app')
@section('title', $link->nome)
@section('titulo', $link->nome)
@section('content')
@php($user = auth()->user())
<div class="bi-report-page">
    <div class="bi-report-barra">
        <span class="bi-report-info shell-watermark text-truncate">
            <i class="bi bi-person-badge"></i> {{ $user->email ?: 'E-mail não informado' }} · {{ $empresaAtiva?->nome ?? 'Empresa não informada' }} · {{ data_local() }}
        </span>
        <span class="bi-report-botoes">
            <a href="{{ route('links.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Relatórios</a>
            <button type="button" class="btn btn-primary btn-sm" id="biTelaCheia" hidden><i class="bi bi-arrows-fullscreen"></i> Tela cheia</button>
        </span>
    </div>
    <div class="bi-report-frame border rounded shadow-sm overflow-hidden" id="biReportFrame">
        <iframe src="{{ $link->url }}" title="{{ $link->nome }}"></iframe>
        {{-- Camada de auditoria (LGPD): não é exibida para Diretor nem Admin da Empresa. --}}
        @unless ($user->isDiretor() || $user->isAdminEmpresa())
        <div class="bi-report-audit-watermark" aria-hidden="true">
            @for ($i = 0; $i < 2; $i++)
            <div class="bi-report-audit-watermark-line">
                <span>Usuário: {{ $user->nome_exibicao }}</span>
                <span>E-mail: {{ $user->email ?: 'Não informado' }}</span>
                <span>IP: {{ request()->ip() ?? 'Não informado' }}</span>
                <span>Data: {{ data_local(null, 'd/m/Y H:i:s') }}</span>
                <span>Setor: {{ $user->funcionario?->setor?->nome ?? 'Não informado' }}</span>
                <strong>Proibido o compartilhamento dessa imagem. Sujeito a penalidade de acordo com a LGPD.</strong>
            </div>
            @endfor
        </div>
        @endunless
        <div class="bi-report-cover" aria-hidden="true">
            <span>MMI Incorporações</span>
        </div>
    </div>
    <p class="text-muted small mt-2 mb-0">
        Se o conteúdo não aparecer acima, o site de origem pode bloquear a exibição dentro desta página.
    </p>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Tela cheia do quadro inteiro (iframe + marca d'água), para a camada LGPD continuar visível.
    var botao = document.getElementById("biTelaCheia");
    var quadro = document.getElementById("biReportFrame");
    if (!botao || !quadro || !quadro.requestFullscreen) return;
    botao.hidden = false;
    botao.addEventListener("click", function () { quadro.requestFullscreen().catch(function () {}); });
})();
</script>
@endpush
