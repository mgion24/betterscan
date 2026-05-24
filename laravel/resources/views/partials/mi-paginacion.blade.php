@if ($paginator->hasPages())
    <nav class="pagination-nav flex gap-2">
        {{-- Botón Anterior --}}
        @if ($paginator->onFirstPage())
            <span class="btn btn-primary disabled">«</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-primary">«</a>
        @endif

        {{-- Números de Páginas --}}
        @foreach ($elements as $element)
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="btn btn-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="btn btn-primary">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Botón Siguiente --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-primary">»</a>
        @else
            <span class="btn btn-primary disabled">»</span>
        @endif
    </nav>
@endif