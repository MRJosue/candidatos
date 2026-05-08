@props(['status'])

@php
    $label = \App\Models\JobApplication::statusLabelFor($status);

    $palette = [
        'applied' => ['background' => '#dbeafe', 'text' => '#1d4ed8', 'ring' => '#bfdbfe'],
        'active' => ['background' => '#dcfce7', 'text' => '#15803d', 'ring' => '#bbf7d0'],
        'rejected' => ['background' => '#fee2e2', 'text' => '#b91c1c', 'ring' => '#fecaca'],
        'withdrawn' => ['background' => '#f3f4f6', 'text' => '#4b5563', 'ring' => '#e5e7eb'],
        'hired' => ['background' => '#ccfbf1', 'text' => '#0f766e', 'ring' => '#99f6e4'],
    ][$status] ?? ['background' => '#f3f4f6', 'text' => '#4b5563', 'ring' => '#e5e7eb'];

    $style = "display: inline-flex; max-width: 100%; align-items: center; border-radius: 9999px; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 500; line-height: 1rem; background-color: {$palette['background']}; color: {$palette['text']}; box-shadow: inset 0 0 0 1px {$palette['ring']};";
@endphp

<span {{ $attributes->merge(['style' => $style]) }}>
    <span style="margin-right: 0.375rem; height: 0.5rem; width: 0.5rem; flex-shrink: 0; border-radius: 9999px; background-color: currentColor; opacity: 0.7;"></span>
    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $label }}</span>
</span>
