@php
    $templateSlug = $profile->template?->slug ?? 'clasico-profesional';
    $lines = fn ($value) => collect(preg_split('/\r\n|\r|\n/', (string) $value))
        ->map(fn ($line) => trim($line))
        ->filter()
        ->values();
    $technicalSkills = $profile->skills->filter(fn ($skill) => ($skill->type ?: 'skill') === 'skill')->values();
    $languageSkills = $profile->skills->filter(fn ($skill) => $skill->type === 'language')->values();
    $softSkills = $profile->skills->filter(fn ($skill) => $skill->type === 'soft_skill')->values();
    $skillGroups = $technicalSkills->groupBy(fn ($skill) => $skill->category ?: ($profile->skills_section_title ?: 'Habilidades'));
    $languageGroups = $languageSkills->groupBy(fn ($skill) => $skill->category ?: 'Idiomas');
    $softSkillGroups = $softSkills->groupBy(fn ($skill) => $skill->category ?: ($profile->soft_skills_section_title ?: 'Habilidades blandas'));
    $skillsTitle = $profile->skills_section_title ?: 'Habilidades';
    $softSkillsTitle = $profile->soft_skills_section_title ?: 'Habilidades blandas';
    $sectionOrder = $profile->normalizedSectionOrder();
    $sideSectionKeys = ['skills', 'languages', 'soft_skills'];
    $mainSectionKeys = ['experiences', 'education'];
    $sideSectionOrder = collect($sectionOrder['side'])
        ->filter(fn ($section) => in_array($section, $sideSectionKeys, true))
        ->unique()
        ->merge(collect($sideSectionKeys)->diff($sectionOrder['side']))
        ->values();
    $mainSectionOrder = collect($sectionOrder['main'])
        ->filter(fn ($section) => in_array($section, $mainSectionKeys, true))
        ->unique()
        ->merge(collect($mainSectionKeys)->diff($sectionOrder['main']))
        ->values();
    $contactItems = collect([
        ['label' => 'Ubicación', 'value' => $profile->location, 'href' => null, 'icon' => null],
        ['label' => 'Correo electrónico', 'value' => $profile->email, 'href' => $profile->email ? 'mailto:'.$profile->email : null, 'icon' => null],
        ['label' => 'Teléfono', 'value' => $profile->phone, 'href' => null, 'icon' => null],
        ['label' => 'LinkedIn', 'value' => $profile->linkedin_url, 'href' => $profile->linkedin_url, 'icon' => 'in'],
        ['label' => 'Portafolio', 'value' => $profile->portfolio_url, 'href' => $profile->portfolio_url, 'icon' => 'www'],
    ])->filter(fn ($item) => filled($item['value']))->values();
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter; margin: 18px 22px; }
        * { box-sizing: border-box; }
        html {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #172033;
            font-size: 10px;
            line-height: 1.36;
            margin: 0;
            padding: 0;
            width: 738px;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        h1, h2, h3, p { margin: 0; }
        h1 { font-size: 26px; line-height: 1.04; letter-spacing: 0; overflow-wrap: break-word; }
        h2 {
            font-size: 11px;
            letter-spacing: 0.8px;
            margin: 12px 0 5px;
            text-transform: uppercase;
        }
        h3 { font-size: 11px; line-height: 1.18; }
        ul { margin: 3px 0 0 14px; padding: 0; }
        li { margin-bottom: 1px; }
        table {
            max-width: 100%;
        }
        td {
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        a {
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .muted { color: #657084; }
        .soft { color: #7b8496; }
        .tiny { font-size: 9px; }
        .small { font-size: 10px; }
        .item { margin-bottom: 8px; page-break-inside: avoid; }
        .card { border: 1px solid #dde3ed; background: #fbfcfe; padding: 7px 9px; margin-bottom: 7px; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.7px; color: #6b7280; }
        .row-meta { color: #667085; font-size: 9.5px; margin-bottom: 2px; }
        .skill {
            display: inline-block;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 2px 5px;
            margin: 2px 3px 2px 0;
        }
        .split { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .split td { vertical-align: top; padding: 0; }
        .right {
            text-align: right;
            white-space: normal;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }
        .pdf-page {
            width: 738px;
        }
        .accent-rule { height: 3px; background: #2f6f73; margin: 8px 0 9px; }
        .summary-box { background: #edf7f5; border-left: 3px solid #2f6f73; padding: 7px 9px; margin-top: 8px; }
        .pill-row { margin-top: 5px; }
        .pill { display: inline-block; background: #eef2f7; color: #344054; padding: 2px 5px; margin: 1px 2px 0 0; }
        .contact-list { font-size: 0; line-height: 1.35; }
        .contact-item {
            display: inline-block;
            font-size: 9.5px;
            vertical-align: middle;
            margin: 0 4px 2px 0;
        }
        .contact-item + .contact-item:before {
            content: "|";
            color: #9aa4b2;
            margin-right: 4px;
        }
        .contact-link { color: inherit; text-decoration: none; }
        .contact-link .contact-text { display: none; }
        .contact-icon {
            display: inline-block;
            min-width: 14px;
            height: 14px;
            padding: 1px 3px;
            border: 1px solid #9aa4b2;
            border-radius: 2px;
            color: #172033;
            font-size: 7px;
            font-weight: 700;
            line-height: 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .template-classic .hero { border-bottom: 1px solid #d7dee8; padding-bottom: 8px; }
        .template-classic .headline { font-size: 12px; color: #2f6f73; margin-top: 3px; }
        .template-classic h2 { color: #2f6f73; border-bottom: 1px solid #a6c7c4; padding-bottom: 4px; }
        .template-classic .section-grid { width: 100%; border-collapse: collapse; }
        .template-classic .section-grid td { vertical-align: top; }
        .template-classic .main-col { width: 67%; padding-right: 14px; }
        .template-classic .side-col { width: 33%; border-left: 1px solid #d7dee8; padding-left: 11px; }
        .template-classic .side-block { background: #f7fafc; border: 1px solid #e1e7ef; padding: 7px; margin-bottom: 7px; }

        .template-academic { color: #111827; font-size: 8.2px; line-height: 1.25; }
        .template-academic p,
        .template-academic li,
        .template-academic td,
        .template-academic div {
            max-width: 100%;
            overflow-wrap: break-word;
            word-wrap: break-word;
            word-break: break-word;
        }
        .template-academic .masthead { text-align: center; border: 1px solid #111827; padding: 6px 10px 5px; }
        .template-academic h1 { font-size: 17px; line-height: 1.02; }
        .template-academic .masthead p { line-height: 1.18; }
        .template-academic .contact { margin-top: 2px; color: #4b5563; }
        .template-academic h2 {
            border-bottom: 1.5px solid #111827;
            color: #111827;
            padding-bottom: 2px;
            margin-top: 7px;
            margin-bottom: 3px;
        }
        .template-academic .academic-note { background: #f5f5f4; border-left: 3px solid #737373; padding: 4px 7px; margin-top: 5px; }
        .template-academic .entry { padding-bottom: 3px; border-bottom: 1px solid #e5e7eb; margin-bottom: 4px; }
        .template-academic .entry:last-child { border-bottom: 0; }
        .template-academic .split td:first-child { width: 72%; padding-right: 8px; }
        .template-academic .split .right {
            width: 28%;
            padding-left: 4px;
            text-align: left;
            font-size: 8px;
            line-height: 1.18;
        }
        .template-academic ul { margin-top: 2px; margin-left: 12px; }
        .template-academic li { margin-bottom: 0; }

        .template-sidebar { width: 100%; border-collapse: collapse; }
        .template-sidebar td { vertical-align: top; }
        .sidebar {
            width: 31%;
            background: #24323f;
            color: #f8fafc;
            padding: 14px 12px;
        }
        .sidebar h1 { font-size: 22px; }
        .sidebar h2 {
            color: #f8fafc;
            border-bottom: 1px solid #8aa0b4;
            padding-bottom: 5px;
            margin-top: 12px;
        }
        .sidebar .muted, .sidebar .small, .sidebar .tiny { color: #d8e0ea; }
        .sidebar .side-line { height: 3px; background: #e5b76d; margin: 8px 0 9px; }
        .sidebar .skill-list { margin-bottom: 5px; }
        .sidebar .meter { height: 3px; background: #526575; margin: 2px 0 4px; }
        .sidebar .meter span { display: block; height: 3px; background: #e5b76d; }
        .main {
            width: 69%;
            padding: 14px 0 12px 16px;
        }
        .main h2 {
            color: #24323f;
            border-bottom: 2px solid #24323f;
            padding-bottom: 5px;
        }
        .main .spotlight { border: 1px solid #d7dee8; background: #fbfaf7; padding: 7px 9px; margin-bottom: 8px; }

        @media screen {
            html {
                background: #e5e7eb;
            }

            body {
                width: 8.5in;
                max-width: calc(100vw - 48px);
                min-height: 11in;
                margin: 24px auto;
                padding: 18px 22px;
                background: #ffffff;
                box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
            }
        }
    </style>
</head>
<body class="pdf-{{ $templateSlug }}">
<div class="pdf-page">
@if ($templateSlug === 'creativo-sidebar')
    <table class="template-sidebar">
        <tr>
            <td class="sidebar">
                <h1>{{ $profile->full_name }}</h1>
                @if ($profile->headline)<p class="muted">{{ $profile->headline }}</p>@endif
                @if ($profile->tagline)<p class="tiny" style="margin-top: 6px;">{{ $profile->tagline }}</p>@endif
                <div class="side-line"></div>

                <h2>Contacto</h2>
                @foreach ($contactItems as $item)
                    <p class="small">
                        @if ($item['icon'])
                            <a class="contact-link" href="{{ $item['href'] }}" title="{{ $item['label'] }}">
                                <span class="contact-icon">{{ $item['icon'] }}</span>
                                <span class="contact-text">{{ $item['value'] }}</span>
                            </a>
                        @else
                            {{ $item['value'] }}
                        @endif
                    </p>
                @endforeach

                @if ($profile->awards)
                    <h2>Premios</h2>
                    <ul>@foreach ($lines($profile->awards) as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif

                @foreach ($sideSectionOrder as $section)
                    @if ($section === 'skills' && $technicalSkills->isNotEmpty())
                        <h2>{{ $skillsTitle }}</h2>
                        @foreach ($skillGroups as $category => $skills)
                            <div class="skill-list">
                                <p><strong>{{ $category }}</strong></p>
                                @foreach ($skills as $skill)
                                    <p class="small">{{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif</p>
                                    @if ($skill->level)
                                        <div class="meter"><span style="width: {{ min(100, max(0, $skill->level * 20)) }}%;"></span></div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    @elseif ($section === 'languages' && $languageSkills->isNotEmpty())
                        <h2>Idiomas</h2>
                        @foreach ($languageGroups as $category => $skills)
                            <div class="skill-list">
                                <p><strong>{{ $category }}</strong></p>
                                @foreach ($skills as $skill)
                                    <p class="small">{{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif</p>
                                    @if ($skill->level)
                                        <div class="meter"><span style="width: {{ min(100, max(0, $skill->level * 20)) }}%;"></span></div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    @elseif ($section === 'soft_skills' && $softSkills->isNotEmpty())
                        <h2>{{ $softSkillsTitle }}</h2>
                        @foreach ($softSkillGroups as $category => $skills)
                            <div class="skill-list">
                                <p><strong>{{ $category }}</strong></p>
                                @foreach ($skills as $skill)
                                    <p class="small">{{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif</p>
                                    @if ($skill->level)
                                        <div class="meter"><span style="width: {{ min(100, max(0, $skill->level * 20)) }}%;"></span></div>
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    @endif
                @endforeach

                @if ($profile->interests)
                    <h2>Intereses</h2>
                    <p class="small">{{ $lines($profile->interests)->join(', ') }}</p>
                @endif
            </td>
            <td class="main">
                @if ($profile->summary || $profile->objective)
                    <div class="spotlight">
                        @if ($profile->summary)<p>{{ $profile->summary }}</p>@endif
                        @if ($profile->objective)<p style="margin-top: 7px;"><strong>Objetivo:</strong> {{ $profile->objective }}</p>@endif
                    </div>
                @endif

                @foreach ($mainSectionOrder as $section)
                    @if ($section === 'experiences')
                        <h2>Experiencia de trabajo</h2>
                        @foreach ($profile->experiences as $item)
                            <div class="item">
                                <p class="row-meta">{{ $item->start_date?->format('m/Y') }} - {{ $item->is_current ? 'Actual' : $item->end_date?->format('m/Y') }}</p>
                                <h3>{{ $item->position }}</h3>
                                <p class="muted">{{ $item->company }}@if($item->location), {{ $item->location }}@endif</p>
                                <ul>@foreach ($lines($item->description) as $line)<li>{{ $line }}</li>@endforeach</ul>
                            </div>
                        @endforeach
                    @elseif ($section === 'education')
                        <h2>Educación</h2>
                        @foreach ($profile->education as $item)
                            <div class="item">
                                <p class="row-meta">{{ $item->start_date?->format('Y') }} - {{ $item->end_date?->format('Y') }}</p>
                                <h3>{{ $item->degree }}</h3>
                                <p class="muted">{{ $item->institution }}@if($item->location), {{ $item->location }}@endif</p>
                                @if ($item->field)<p>{{ $item->field }}</p>@endif
                                @if ($item->description)<p>{{ $item->description }}</p>@endif
                            </div>
                        @endforeach
                    @endif
                @endforeach

                @if ($profile->leadership_activities)
                    <h2>Liderazgo y actividades</h2>
                    <ul>@foreach ($lines($profile->leadership_activities) as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif
            </td>
        </tr>
    </table>
@elseif ($templateSlug === 'academico-bullet')
    <div class="template-academic">
        <div class="masthead">
            <h1>{{ $profile->full_name }}</h1>
            @if ($profile->headline)<p>{{ $profile->headline }}</p>@endif
            <p class="contact contact-list">
                @foreach ($contactItems as $item)
                    <span class="contact-item">
                        @if ($item['icon'])
                            <a class="contact-link" href="{{ $item['href'] }}" title="{{ $item['label'] }}">
                                <span class="contact-icon">{{ $item['icon'] }}</span>
                                <span class="contact-text">{{ $item['value'] }}</span>
                            </a>
                        @else
                            {{ $item['value'] }}
                        @endif
                    </span>
                @endforeach
            </p>
        </div>
        @if ($profile->summary || $profile->objective)
            <div class="academic-note">
                @if ($profile->summary)<p>{{ $profile->summary }}</p>@endif
                @if ($profile->objective)<p style="margin-top: 6px;"><strong>Objetivo:</strong> {{ $profile->objective }}</p>@endif
            </div>
        @endif

        @foreach ($mainSectionOrder as $section)
            @if ($section === 'education')
                <h2>Educación</h2>
                @foreach ($profile->education as $item)
                    <div class="entry">
                        <table class="split"><tr><td><strong>{{ $item->institution }}</strong></td><td class="right">{{ $item->location }}</td></tr></table>
                        <table class="split"><tr><td>{{ $item->degree }}@if($item->field), {{ $item->field }}@endif @if($item->gpa) · Promedio {{ $item->gpa }}@endif</td><td class="right">{{ $item->end_date?->format('M Y') }}</td></tr></table>
                        @if ($item->honors)<p><strong>Honores:</strong> {{ $item->honors }}</p>@endif
                        @if ($item->thesis)<p><strong>Tesis:</strong> {{ $item->thesis }}</p>@endif
                        @if ($item->relevant_coursework)<p><strong>Cursos relevantes:</strong> {{ $item->relevant_coursework }}</p>@endif
                        @if ($item->description)<ul>@foreach ($lines($item->description) as $line)<li>{{ $line }}</li>@endforeach</ul>@endif
                    </div>
                @endforeach
            @elseif ($section === 'experiences')
                <h2>Experiencia</h2>
                @foreach ($profile->experiences as $item)
                    <div class="entry">
                        <table class="split"><tr><td><strong>{{ $item->company }}</strong></td><td class="right">{{ $item->location }}</td></tr></table>
                        <table class="split"><tr><td>{{ $item->position }}</td><td class="right">{{ $item->start_date?->format('M Y') }} - {{ $item->is_current ? 'Actual' : $item->end_date?->format('M Y') }}</td></tr></table>
                        <ul>@foreach ($lines($item->description) as $line)<li>{{ $line }}</li>@endforeach</ul>
                    </div>
                @endforeach
            @endif
        @endforeach

        @if ($profile->leadership_activities)
            <h2>Liderazgo y actividades</h2>
            <ul>@foreach ($lines($profile->leadership_activities) as $line)<li>{{ $line }}</li>@endforeach</ul>
        @endif

        @foreach ($sideSectionOrder as $section)
            @if ($section === 'skills' && $technicalSkills->isNotEmpty())
                <h2>{{ $skillsTitle }}</h2>
                @foreach ($skillGroups as $category => $skills)
                    <p><strong>{{ $category }}:</strong> {{ $skills->map(fn ($skill) => $skill->name)->join(', ') }}</p>
                @endforeach
            @elseif ($section === 'languages' && $languageSkills->isNotEmpty())
                <h2>Idiomas</h2>
                @foreach ($languageGroups as $category => $skills)
                    <p><strong>{{ $category }}:</strong> {{ $skills->map(fn ($skill) => $skill->name)->join(', ') }}</p>
                @endforeach
            @elseif ($section === 'soft_skills' && $softSkills->isNotEmpty())
                <h2>{{ $softSkillsTitle }}</h2>
                @foreach ($softSkillGroups as $category => $skills)
                    <p><strong>{{ $category }}:</strong> {{ $skills->map(fn ($skill) => $skill->name)->join(', ') }}</p>
                @endforeach
            @endif
        @endforeach
        @if ($profile->interests)<h2>Intereses</h2><p>{{ $lines($profile->interests)->join(', ') }}</p>@endif
    </div>
@else
    <div class="template-classic">
        <div class="hero">
            <table class="split">
                <tr>
                    <td style="width: 67%; padding-right: 16px;">
                        <h1>{{ $profile->full_name }}</h1>
                        @if ($profile->headline)<p class="headline">{{ $profile->headline }}</p>@endif
                        @if ($profile->tagline)<p class="soft" style="margin-top: 4px;">{{ $profile->tagline }}</p>@endif
                    </td>
                    <td class="right small" style="width: 33%; color: #485467;">
                        @foreach ($contactItems as $item)
                            <p>
                                @if ($item['icon'])
                                    <a class="contact-link" href="{{ $item['href'] }}" title="{{ $item['label'] }}">
                                        <span class="contact-icon">{{ $item['icon'] }}</span>
                                        <span class="contact-text">{{ $item['value'] }}</span>
                                    </a>
                                @else
                                    {{ $item['value'] }}
                                @endif
                            </p>
                        @endforeach
                    </td>
                </tr>
            </table>
            <div class="accent-rule"></div>
            @if ($profile->summary)<div class="summary-box">{{ $profile->summary }}</div>@endif
        </div>

        <table class="section-grid" style="margin-top: 12px;">
            <tr>
                <td class="main-col">
                    @if ($profile->objective)<h2>Objetivo</h2><p>{{ $profile->objective }}</p>@endif

                    @foreach ($mainSectionOrder as $section)
                        @if ($section === 'experiences')
                            <h2>Experiencia</h2>
                            @foreach ($profile->experiences as $item)
                                <div class="item">
                                    <table class="split">
                                        <tr>
                                            <td><h3>{{ $item->position }}</h3></td>
                                            <td class="right row-meta">{{ $item->start_date?->format('m/Y') }} - {{ $item->is_current ? 'Actual' : $item->end_date?->format('m/Y') }}</td>
                                        </tr>
                                    </table>
                                    <p class="muted">{{ $item->company }}@if($item->location), {{ $item->location }}@endif</p>
                                    <ul>@foreach ($lines($item->description) as $line)<li>{{ $line }}</li>@endforeach</ul>
                                </div>
                            @endforeach
                        @elseif ($section === 'education')
                            <h2>Educación</h2>
                            @foreach ($profile->education as $item)
                                <div class="item">
                                    <table class="split">
                                        <tr>
                                            <td><h3>{{ $item->degree }}</h3></td>
                                            <td class="right row-meta">{{ $item->end_date?->format('Y') }}</td>
                                        </tr>
                                    </table>
                                    <p class="muted">{{ $item->institution }}@if($item->location), {{ $item->location }}@endif</p>
                                    <p>{{ collect([$item->field, $item->gpa ? 'Promedio '.$item->gpa : null, $item->honors])->filter()->join(' · ') }}</p>
                                    @if ($item->description)<p>{{ $item->description }}</p>@endif
                                    @if ($item->relevant_coursework)<p><strong>Cursos relevantes:</strong> {{ $item->relevant_coursework }}</p>@endif
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </td>
                <td class="side-col">
                    @foreach ($sideSectionOrder as $section)
                        @if ($section === 'skills' && $technicalSkills->isNotEmpty())
                            <div class="side-block">
                                <p class="label">{{ $skillsTitle }}</p>
                                <div class="pill-row">
                                    @foreach ($technicalSkills as $skill)<span class="skill">{{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif</span>@endforeach
                                </div>
                            </div>
                        @elseif ($section === 'languages' && $languageSkills->isNotEmpty())
                            <div class="side-block">
                                <p class="label">Idiomas</p>
                                <div class="pill-row">
                                    @foreach ($languageSkills as $skill)<span class="skill">{{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif</span>@endforeach
                                </div>
                            </div>
                        @elseif ($section === 'soft_skills' && $softSkills->isNotEmpty())
                            <div class="side-block">
                                <p class="label">{{ $softSkillsTitle }}</p>
                                <div class="pill-row">
                                    @foreach ($softSkills as $skill)<span class="skill">{{ $skill->name }}@if($skill->level) · {{ $skill->level }}/5 @endif</span>@endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if ($profile->awards)
                        <div class="side-block">
                            <p class="label">Premios y reconocimientos</p>
                            <ul>@foreach ($lines($profile->awards) as $line)<li>{{ $line }}</li>@endforeach</ul>
                        </div>
                    @endif

                    @if ($profile->leadership_activities)
                        <div class="side-block">
                            <p class="label">Liderazgo y actividades</p>
                            <ul>@foreach ($lines($profile->leadership_activities) as $line)<li>{{ $line }}</li>@endforeach</ul>
                        </div>
                    @endif

                    @if ($profile->interests)
                        <div class="side-block">
                            <p class="label">Intereses</p>
                            <p>{{ $lines($profile->interests)->join(', ') }}</p>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
@endif
</div>
</body>
</html>
