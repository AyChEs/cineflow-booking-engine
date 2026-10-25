{{--
    Logotipo: símbolo + nombre. El nombre sale de config('app.name'), así que cambiar
    de nombre es cambiar APP_NAME (y los SVG de public/img/marca). El texto real lo leen
    los lectores de pantalla; el símbolo es decorativo.
--}}
@props(['tam' => 'md'])
<span {{ $attributes->merge(['class' => 'marca-logo '.($tam === 'lg' ? 'marca-logo--lg' : 'marca-logo--md')]) }}>
    <x-marca.simbolo class="marca-logo-simbolo" />
    <span class="marca-logo-nombre">{{ config('app.name') }}</span>
</span>
