<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @if (auth()->user()->isAdmin())
        <li class="breadcrumb-item"><a href="{{ route('empresas.index') }}">Empresas</a></li>
        @endif
        <li class="breadcrumb-item"><a href="{{ route('empresas.show', $empresa) }}">{{ $empresa->nome }}</a></li>
        @foreach ($trilha ?? [] as $rotulo => $url)
        <li class="breadcrumb-item"><a href="{{ $url }}">{{ $rotulo }}</a></li>
        @endforeach
        <li class="breadcrumb-item active">{{ $atual }}</li>
    </ol>
</nav>
