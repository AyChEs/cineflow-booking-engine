{{-- Progreso de la compra: Entradas, Butacas, Pago. --}}
<div class="border-b border-sala-700 bg-sala-900">
    <div class="mx-auto flex max-w-5xl items-center gap-4 px-4 py-3 sm:px-6">
        <nav aria-label="Pasos de la compra" class="flex-1">
            <ol class="grid grid-cols-3 gap-2 text-sm">
                @foreach ([1 => 'Entradas', 2 => 'Butacas', 3 => 'Pago'] as $n => $nombre)
                    <li class="flex items-center gap-2 border-b-2 pb-2 {{ $step >= $n ? 'border-carmin-400' : 'border-sala-700' }}"
                        @if ($step === $n) aria-current="step" @endif>
                        <span class="cifras flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold
                                     {{ $step > $n ? 'border-ok text-ok' : ($step === $n ? 'border-carmin-400 text-texto' : 'border-sala-700 text-texto-2') }}">
                            @if ($step > $n)
                                <x-icon name="check" class="h-3.5 w-3.5" /><span class="sr-only">Hecho:</span>
                            @else
                                {{ $n }}
                            @endif
                        </span>
                        <span class="{{ $step === $n ? 'font-semibold text-texto' : 'text-texto-2' }}">{{ $nombre }}</span>
                    </li>
                @endforeach
            </ol>
        </nav>
        <a href="{{ route('compra.cancel') }}" class="btn btn-secondary btn-sm shrink-0">
            <x-icon name="close" class="h-4 w-4" /><span class="hidden sm:inline">Cancelar</span><span class="sr-only sm:hidden">Cancelar la compra</span>
        </a>
    </div>
</div>
