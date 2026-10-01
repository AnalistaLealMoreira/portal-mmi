@props(['placeholder' => 'Buscar...', 'manter' => []])
{{-- Busca por texto (GET ?q=). Os parâmetros listados em "manter" (ex.: setor) seguem junto. --}}
<form method="get" class="busca" role="search">
    @foreach ($manter as $param)
        @if (request()->filled($param))<input type="hidden" name="{{ $param }}" value="{{ request($param) }}">@endif
    @endforeach
    <i class="bi bi-search busca-icone" aria-hidden="true"></i>
    <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm busca-campo"
           placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
    @if (request()->filled('q'))
    <a href="{{ request()->fullUrlWithoutQuery(['q', 'page']) }}" class="busca-limpar" title="Limpar busca" aria-label="Limpar busca"><i class="bi bi-x-lg"></i></a>
    @endif
</form>
