{{--
    Tarjeta de película: el título es el enlace y su ::after cubre toda la tarjeta.
    variante «tira»: fotograma de la tira de celuloide de la portada (título sobre el póster).
    Las dos se inclinan en 3D y llevan un foco que sigue al cursor (data-inclinar, movimiento.js).
--}}
@props(['pelicula', 'nivel' => 'h3', 'prioridad' => false, 'variante' => 'lista'])
@php
    $enlace = route('peliculas.show', $pelicula);
    $nombreVista = 'poster-'.$pelicula->id;
    $generos = $pelicula->categorias->take(3);
@endphp

@if ($variante === 'tira')
<article {{ $attributes->merge(['class' => 'frame group']) }} data-inclinar>
    <div class="frame-cuadro">
        <img data-respaldo="{{ $pelicula->cartel_respaldo_url }}" onerror="this.onerror=null;this.src=this.dataset.respaldo"
             src="{{ $pelicula->poster_url }}" alt="Póster de {{ $pelicula->titulo }}"
             width="300" height="450" loading="lazy" decoding="async" draggable="false"
             class="frame-img" style="view-transition-name: {{ $nombreVista }}">
        <span class="frame-foco" aria-hidden="true"></span>
        <div class="frame-pie">
            <{{ $nivel }} class="frame-titulo">
                <a href="{{ $enlace }}" class="frame-enlace">{{ $pelicula->titulo }}</a>
            </{{ $nivel }}>
            <p class="frame-meta cifras">
                @if ($pelicula->duracion_min){{ $pelicula->duracion_min }} min @endif
                @if ($pelicula->duracion_min && $generos->isNotEmpty())<span aria-hidden="true">·</span>@endif
                {{ $generos->pluck('nombre')->take(2)->implode(', ') }}
            </p>
        </div>
    </div>
</article>
@else
<article {{ $attributes->merge(['class' => 'group relative flex flex-col gap-3']) }} data-inclinar>
    <div class="frame-cuadro overflow-hidden rounded-card border border-sala-700 bg-sala-850">
        <img data-respaldo="{{ $pelicula->cartel_respaldo_url }}" onerror="this.onerror=null;this.src=this.dataset.respaldo"
             src="{{ str_replace('/t/p/w500/', '/t/p/w342/', $pelicula->poster_url) }}" alt="Póster de {{ $pelicula->titulo }}"
             width="300" height="450"
             @if ($prioridad) fetchpriority="high" @else loading="lazy" @endif decoding="async"
             class="aspect-[2/3] w-full object-cover" style="view-transition-name: {{ $nombreVista }}">
        <span class="frame-foco" aria-hidden="true"></span>
    </div>
    <div class="flex flex-col gap-1.5">
        <{{ $nivel }} class="text-lg font-semibold leading-tight text-texto">
            <a href="{{ $enlace }}"
               class="no-underline after:absolute after:inset-0 after:rounded-card after:content-[''] group-hover:text-carmin-400 focus-visible:outline-none focus-visible:after:outline focus-visible:after:outline-2 focus-visible:after:outline-offset-4 focus-visible:after:outline-foco">{{ $pelicula->titulo }}</a>
        </{{ $nivel }}>
        <p class="cifras text-sm text-texto-2">
            @if ($pelicula->duracion_min){{ $pelicula->duracion_min }} min @endif
            @if ($pelicula->duracion_min && $pelicula->classificacio_edad)<span aria-hidden="true"> · </span>@endif
            @if ($pelicula->classificacio_edad)<span>{{ $pelicula->classificacio_edad === 'TP' ? 'Todos los públicos' : $pelicula->classificacio_edad }}</span>@endif
        </p>
        @if ($generos->isNotEmpty())
            <ul class="flex flex-wrap gap-1.5" aria-label="Géneros">
                @foreach ($generos as $categoria)
                    <li class="rounded-full border border-sala-700 px-2 text-xs text-texto-2">{{ $categoria->nombre }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</article>
@endif
