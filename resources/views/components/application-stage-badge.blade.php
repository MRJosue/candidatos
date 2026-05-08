@props(['stage'])

@php
    $normalizedStage = \App\Models\JobApplication::normalizedStage($stage);
    $label = \App\Models\JobApplication::stageLabelFor($stage);

    $palette = [
        'waiting_feedback' => ['background' => '#f3d8e5', 'text' => '#7a3f5f', 'ring' => '#ead0dc'],
        'socioeconomic_study' => ['background' => '#dcebe3', 'text' => '#3d6f57', 'ring' => '#cfe2d8'],
        'psychometric_tests' => ['background' => '#f2dcc8', 'text' => '#795335', 'ring' => '#ead0b7'],
        'technical_interview' => ['background' => '#ead9c6', 'text' => '#72583d', 'ring' => '#dec6ad'],
        'review' => ['background' => '#d5e5f6', 'text' => '#2d5f87', 'ring' => '#c5d9ee'],
        'hr_interview' => ['background' => '#e8d6f4', 'text' => '#765099', 'ring' => '#ddc6ed'],
        'offer_sent' => ['background' => '#f1e7bf', 'text' => '#786323', 'ring' => '#e4d79f'],
        'hired' => ['background' => '#dcebe3', 'text' => '#3d6f57', 'ring' => '#cfe2d8'],
        'rejected' => ['background' => '#f1d8d5', 'text' => '#8a4943', 'ring' => '#e7c7c3'],
    ][$normalizedStage] ?? ['background' => '#f3f4f6', 'text' => '#4b5563', 'ring' => '#e5e7eb'];

    $style = "display: inline-flex; max-width: 100%; align-items: center; border-radius: 9999px; padding: 0.25rem 0.625rem; font-size: 0.75rem; font-weight: 500; line-height: 1rem; background-color: {$palette['background']}; color: {$palette['text']}; box-shadow: inset 0 0 0 1px {$palette['ring']};";
@endphp

<span {{ $attributes->merge(['style' => $style]) }}>
    <span style="margin-right: 0.375rem; height: 0.5rem; width: 0.5rem; flex-shrink: 0; border-radius: 9999px; background-color: currentColor; opacity: 0.7;"></span>
    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $label }}</span>
</span>
