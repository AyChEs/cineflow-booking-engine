@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-[2.75rem] border-[color:var(--border-subtle)] focus:border-[color:var(--color-accent-dark)] focus:ring-[color:var(--color-accent-bright)] disabled:opacity-50 disabled:cursor-not-allowed rounded-md shadow-sm']) }}>
