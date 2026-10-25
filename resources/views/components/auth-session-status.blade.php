@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success', 'role' => 'status']) }}>
        <x-icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0" />{{ $status }}
    </div>
@endif
