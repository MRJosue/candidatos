@props(['stage'])

@php
    $normalizedStage = \App\Models\JobApplication::normalizedStage($stage);
    $label = \App\Models\JobApplication::stageLabelFor($stage);

    $classes = [
        'waiting_feedback' => 'bg-pink-100 text-pink-700 ring-pink-200',
        'socioeconomic_study' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'psychometric_tests' => 'bg-orange-100 text-orange-700 ring-orange-200',
        'technical_interview' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'review' => 'bg-sky-100 text-sky-700 ring-sky-200',
        'hr_interview' => 'bg-purple-100 text-purple-700 ring-purple-200',
        'offer_sent' => 'bg-yellow-100 text-yellow-700 ring-yellow-200',
        'hired' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'rejected' => 'bg-red-100 text-red-700 ring-red-200',
    ][$normalizedStage] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex max-w-full items-center rounded-full px-2.5 py-1 text-xs font-medium leading-4 ring-1 {$classes}"]) }}>
    <span class="mr-1.5 h-2 w-2 shrink-0 rounded-full bg-current opacity-70"></span>
    <span class="truncate">{{ $label }}</span>
</span>
