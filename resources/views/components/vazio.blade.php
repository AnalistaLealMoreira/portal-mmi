@props(['icone' => 'bi-inbox', 'titulo', 'acao' => null, 'acaoUrl' => null])
<div class="estado-vazio">
    <span class="estado-vazio-icone"><i class="bi {{ $icone }}"></i></span>
    <p class="estado-vazio-titulo">{{ $titulo }}</p>
    <p class="estado-vazio-texto">{{ $slot }}</p>
    @if ($acao && $acaoUrl)<a href="{{ $acaoUrl }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> {{ $acao }}</a>@endif
</div>
