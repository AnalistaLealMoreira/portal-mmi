@if ($paginator->hasPages())
<nav>
    <ul class="pagination">
        @if ($paginator->onFirstPage())
        <li class="page-item disabled"><span class="page-link">Anterior</span></li>
        @else
        <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}">Anterior</a></li>
        @endif
        <li class="page-item disabled"><span class="page-link">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span></li>
        @if ($paginator->hasMorePages())
        <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}">Próxima</a></li>
        @else
        <li class="page-item disabled"><span class="page-link">Próxima</span></li>
        @endif
    </ul>
</nav>
@endif
