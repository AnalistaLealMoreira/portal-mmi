<div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="modalExcluirTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content modal-excluir" id="modalExcluirForm">
            @csrf
            <div class="modal-body">
                <span class="modal-excluir-icone"><i class="bi bi-exclamation-triangle"></i></span>
                <h2 class="modal-excluir-titulo" id="modalExcluirTitulo">Excluir <span data-campo="nome"></span>?</h2>
                <p class="modal-excluir-aviso" data-campo="aviso"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger btn-sm" data-campo="confirmar">Excluir definitivamente</button>
            </div>
        </form>
    </div>
</div>
