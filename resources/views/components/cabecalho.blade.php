@props(['descricao' => null])
{{-- Cabeçalho da página: o título fica na topbar; aqui vão a descrição e as ações. --}}
<div class="page-head">
    @if ($descricao)<p class="page-head-desc">{{ $descricao }}</p>@endif
    @if (trim($slot) !== '')<div class="page-head-acoes">{{ $slot }}</div>@endif
</div>
