@props(['active'])

<a {{ $attributes->merge(['class' => 'mobile-menu-link']) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
