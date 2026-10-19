{{-- Tarjeta de película: el título es el enlace y su ::after cubre toda la tarjeta. --}}
@props(['pelicula', 'nivel' => 'h3', 'prioridad' => false])
<article {{ $attributes->merge(['class' => 'group relative flex flex-col gap-3']) }}>
    <div class="overflow-hidden rounded-card border border-sala-700 bg-sala-850">
        <img src="{{ $pelicula->poster_url }}" alt="Póster de {{ $pelicula->titulo }}"
             width="300" height="450"
             @if ($prioridad) fetchpriority="high" @else loading="lazy" @endif decoding="async"
             class="aspect-[2/3] w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
    </div>
    <div class="flex flex-col gap-1.5">
        <{{ $nivel }} class="text-lg font-semibold leading-tight text-texto">
            <a href="{{ route('peliculas.show', $pelicula) }}"
               class="no-underline after:absolute after:inset-0 after:rounded-card after:content-[''] group-hover:text-carmin-400 focus-visible:outline-none focus-visible:after:outline focus-visible:after:outline-2 focus-visible:after:outline-offset-4 focus-visible:after:outline-foco">{{ $pelicula->titulo }}</a>
        </{{ $nivel }}>
        <p class="cifras text-sm text-texto-2">
            @if ($pelicula->duracion_min){{ $pelicula->duracion_min }} min @endif
            @if ($pelicula->duracion_min && $pelicula->classificacio_edad)<span aria-hidden="true"> · </span>@endif
            @if ($pelicula->classificacio_edad)<span>{{ $pelicula->classificacio_edad === 'TP' ? 'Todos los públicos' : $pelicula->classificacio_edad }}</span>@endif
        </p>
        @if ($pelicula->categorias->isNotEmpty())
            <ul class="flex flex-wrap gap-1.5" aria-label="Géneros">
                @foreach ($pelicula->categorias->take(3) as $categoria)
                    <li class="rounded-full border border-sala-700 px-2 text-xs text-texto-2">{{ $categoria->nombre }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</article>
