@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-input disabled:cursor-not-allowed disabled:opacity-60']) }}>
