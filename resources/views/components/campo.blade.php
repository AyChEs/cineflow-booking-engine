{{-- Campo de formulario con etiqueta visible, ayuda y error accesibles. --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'ayuda' => null, 'opcional' => false, 'id' => null, 'bag' => 'default'])
@php
    $id = $id ?? $name;
    $error = $errors->getBag($bag)->first($name);
    $describe = trim(($ayuda ? "{$id}-ayuda " : '').($error ? "{$id}-error" : ''));
@endphp
<div class="form-group">
    <label for="{{ $id }}" class="form-label">
        {{ $label }}@if ($opcional) <span class="font-normal text-texto-2">(opcional)</span>@endif
    </label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           @if ($error) aria-invalid="true" @endif
           @if ($describe !== '') aria-describedby="{{ $describe }}" @endif
           {{ $attributes->merge(['class' => 'form-input']) }}>
    @if ($ayuda)
        <p id="{{ $id }}-ayuda" class="mt-1.5 text-sm text-texto-2">{{ $ayuda }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="form-error"><x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />{{ $error }}</p>
    @endif
</div>
