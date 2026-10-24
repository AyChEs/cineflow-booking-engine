{{-- Paginación del tema: enlaces con nombre, página actual con aria-current. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="cifras text-texto-2">
            Del {{ $paginator->firstItem() }} al {{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </p>
        <ul class="flex flex-wrap items-center gap-1">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="btn btn-secondary btn-sm cursor-not-allowed opacity-60" aria-disabled="true"><x-icon name="arrow-left" class="h-4 w-4" /><span class="sr-only">Página anterior</span></span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm"><x-icon name="arrow-left" class="h-4 w-4" /><span class="sr-only">Página anterior</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="px-2 text-texto-2">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="btn btn-sm cifras border-carmin-400 text-texto">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-secondary btn-sm cifras" aria-label="Página {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm"><x-icon name="arrow-right" class="h-4 w-4" /><span class="sr-only">Página siguiente</span></a>
                @else
                    <span class="btn btn-secondary btn-sm cursor-not-allowed opacity-60" aria-disabled="true"><x-icon name="arrow-right" class="h-4 w-4" /><span class="sr-only">Página siguiente</span></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
