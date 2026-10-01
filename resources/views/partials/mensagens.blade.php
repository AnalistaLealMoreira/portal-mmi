@php($tiposMensagem = ['success' => ['success', 'bi-check-circle'], 'error' => ['danger', 'bi-exclamation-octagon'], 'warning' => ['warning', 'bi-exclamation-triangle'], 'info' => ['info', 'bi-info-circle']])
@auth
{{-- Avisos flutuantes no canto; erros ficam até serem fechados. --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3 toast-portal-area">
    @foreach ($tiposMensagem as $chave => [$classe, $icone])
        @if (session($chave))
        <div class="toast toast-portal toast-{{ $classe }}" role="{{ $classe === 'danger' ? 'alert' : 'status' }}" aria-live="{{ $classe === 'danger' ? 'assertive' : 'polite' }}" aria-atomic="true"
             data-bs-autohide="{{ $classe === 'danger' ? 'false' : 'true' }}" data-bs-delay="5000">
            <div class="d-flex align-items-start">
                <i class="bi {{ $icone }} toast-portal-icone"></i>
                <div class="toast-body">{{ session($chave) }}</div>
                <button type="button" class="btn-close me-2 mt-2" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
        @endif
    @endforeach
</div>
@else
@foreach ($tiposMensagem as $chave => [$classe, $icone])
    @if (session($chave))
    <div class="alert alert-{{ $classe }}">{{ session($chave) }}</div>
    @endif
@endforeach
@endauth
