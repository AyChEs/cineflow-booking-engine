@props(['active'])

<a {{ $attributes->merge(['class' => 'site-nav-link']) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
