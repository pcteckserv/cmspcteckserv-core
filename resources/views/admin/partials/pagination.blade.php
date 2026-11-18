@if ($paginator->hasPages())
    <nav aria-label="Paginação">
        <ul class="pagination flex-wrap mb-0">
            <li @class(['page-item', 'disabled' => $paginator->onFirstPage()])>
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-disabled="true">Anterior</span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">Anterior</a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                @else
                    @foreach ($element as $page => $url)
                        <li @class(['page-item', 'active' => $page === $paginator->currentPage()])>
                            @if ($page === $paginator->currentPage())
                                <span class="page-link" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="page-link" href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li @class(['page-item', 'disabled' => ! $paginator->hasMorePages()])>
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página seguinte">Seguinte</a>
                @else
                    <span class="page-link" aria-disabled="true">Seguinte</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
