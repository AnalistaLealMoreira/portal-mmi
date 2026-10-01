@props(['editar' => null, 'excluir' => null, 'destroy' => null, 'nome' => '', 'aviso' => '', 'bloqueado' => false])
{{--
    Ações da linha: ícones discretos. "Excluir" abre a janela de confirmação
    (#modalExcluir, no layout); sem JavaScript, o link leva à página de confirmação.
--}}
<div class="acoes-linha">
    @if ($editar)
    <a href="{{ $editar }}" class="acao-btn" title="Editar" aria-label="Editar {{ $nome }}"><i class="bi bi-pencil"></i></a>
    @endif
    @if ($excluir)
    <a href="{{ $excluir }}" class="acao-btn acao-perigo js-excluir" title="Excluir" aria-label="Excluir {{ $nome }}"
       data-action="{{ $destroy }}" data-nome="{{ $nome }}" data-aviso="{{ $aviso }}" @if ($bloqueado) data-bloqueado="1" @endif>
        <i class="bi bi-trash3"></i>
    </a>
    @endif
</div>
