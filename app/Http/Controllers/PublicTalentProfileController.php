<?php

namespace App\Http\Controllers;

use App\Models\CvProfile;
use App\Models\CvTemplate;
use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicTalentProfileController extends Controller
{
    public function edit(Talent $talent)
    {
        $talent->load(['cvProfile.experiences', 'cvProfile.education', 'cvProfile.skills']);
        $profile = $talent->cvProfile ?: new CvProfile([
            'talent_id' => $talent->id,
            'cv_template_id' => CvTemplate::defaultTemplate()?->id,
            'title' => 'CV '.$talent->full_name,
            'full_name' => $talent->full_name,
            'email' => $talent->email,
            'phone' => $talent->phone,
            'location' => $talent->location,
            'headline' => $talent->headline ?: $talent->target_position,
            'summary' => $talent->technical_summary,
        ]);

        return view('public-talents.edit', [
            'talent' => $talent,
            'profile' => $profile,
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
            'sectionText' => $talent->cvProfile ? $this->sectionText($talent->cvProfile) : [],
            'linkUsed' => $talent->public_link_submitted_at !== null,
        ]);
    }

    public function update(Request $request, Talent $talent)
    {
        if ($talent->public_link_submitted_at !== null) {
            return redirect()
                ->route('public-talents.edit', ['talent' => $talent->public_token])
                ->withErrors(['public_link' => 'Esta liga ya fue utilizada. Solicita una nueva liga al reclutador si necesitas hacer cambios.']);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:160'],
            'headline' => ['nullable', 'string', 'max:180'],
            'target_position' => ['nullable', 'string', 'max:160'],
            'seniority' => ['nullable', 'string', 'max:80'],
            'availability' => ['nullable', 'string', 'max:120'],
            'technical_stack' => ['nullable', 'string', 'max:1000'],
            'languages' => ['nullable', 'string', 'max:1000'],
            'links' => ['nullable', 'string', 'max:1000'],
            'technical_summary' => ['nullable', 'string', 'max:4000'],
            'cv_template_id' => [
                'nullable',
                Rule::exists('cv_templates', 'id')->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:120'],
            'full_name' => ['required', 'string', 'max:160'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'awards' => ['nullable', 'string', 'max:2000'],
            'leadership_activities' => ['nullable', 'string', 'max:2000'],
            'interests' => ['nullable', 'string', 'max:1000'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'experiences_text' => ['nullable', 'string', 'max:12000'],
            'education_text' => ['nullable', 'string', 'max:8000'],
            'skills_text' => ['nullable', 'string', 'max:4000'],
            'languages_text' => ['nullable', 'string', 'max:4000'],
            'soft_skills_text' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($talent, $data): void {
            $talent = Talent::query()->whereKey($talent->id)->lockForUpdate()->firstOrFail();

            if ($talent->public_link_submitted_at !== null) {
                throw ValidationException::withMessages([
                    'public_link' => 'Esta liga ya fue utilizada. Solicita una nueva liga al reclutador si necesitas hacer cambios.',
                ]);
            }

            $talent->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'location' => $data['location'] ?? null,
                'headline' => $data['headline'] ?? null,
                'target_position' => $data['target_position'] ?? null,
                'seniority' => $data['seniority'] ?? null,
                'availability' => $data['availability'] ?? null,
                'technical_stack' => $this->splitList($data['technical_stack'] ?? null),
                'languages' => $this->splitList($data['languages'] ?? null),
                'links' => $this->splitList($data['links'] ?? null),
                'technical_summary' => $data['technical_summary'] ?? null,
                'public_link_submitted_at' => now(),
            ]);

            $profile = $talent->cvProfile ?: $talent->cvProfile()->make([
                'user_id' => $talent->recruiter_id,
                'section_order' => CvProfile::defaultSectionOrder(),
            ]);

            $profile->fill([
                'user_id' => $talent->recruiter_id,
                'title' => $data['title'],
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'location' => $data['location'] ?? null,
                'headline' => $data['headline'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'summary' => $data['summary'] ?? null,
                'objective' => $data['objective'] ?? null,
                'skills_section_title' => $profile->skills_section_title ?: 'Habilidades',
                'soft_skills_section_title' => $profile->soft_skills_section_title ?: 'Habilidades blandas',
                'cv_template_id' => $data['cv_template_id'] ?? $profile->cv_template_id ?? CvTemplate::defaultTemplate()?->id,
                'awards' => $data['awards'] ?? null,
                'leadership_activities' => $data['leadership_activities'] ?? null,
                'interests' => $data['interests'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'portfolio_url' => $data['portfolio_url'] ?? null,
            ]);

            $profile->save();

            $this->replaceSectionsFromText($profile, $data);
        });

        return redirect()
            ->route('public-talents.edit', ['talent' => $talent->public_token])
            ->with('status', 'Informacion actualizada.');
    }

    /**
     * @return array<string, string>
     */
    private function sectionText(CvProfile $cvProfile): array
    {
        return [
            'experiences' => $cvProfile->experiences
                ->map(fn ($experience) => trim(implode("\n", array_filter([
                    implode(' | ', array_filter([
                        $experience->position,
                        $experience->company,
                        $this->periodFromDates($experience->start_date?->format('Y'), $experience->end_date?->format('Y'), $experience->is_current),
                    ])),
                    $experience->description,
                ]))))
                ->implode("\n\n"),
            'education' => $cvProfile->education
                ->map(fn ($education) => trim(implode("\n", array_filter([
                    implode(' | ', array_filter([
                        $education->degree,
                        $education->institution,
                        $this->periodFromDates($education->start_date?->format('Y'), $education->end_date?->format('Y'), false),
                    ])),
                    $education->description,
                ]))))
                ->implode("\n\n"),
            'skills' => $cvProfile->skills->where('type', 'skill')->pluck('name')->implode("\n"),
            'languages' => $cvProfile->skills->where('type', 'language')->pluck('name')->implode("\n"),
            'soft_skills' => $cvProfile->skills->where('type', 'soft_skill')->pluck('name')->implode("\n"),
        ];
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function replaceSectionsFromText(CvProfile $cvProfile, array $data): void
    {
        $this->replaceExperiences($cvProfile, $this->parseBlocks($data['experiences_text'] ?? '', ['position', 'company']));
        $this->replaceEducation($cvProfile, $this->parseBlocks($data['education_text'] ?? '', ['degree', 'institution']));
        $this->replaceSkills($cvProfile, 'skill', $this->parseList($data['skills_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'language', $this->parseList($data['languages_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'soft_skill', $this->parseList($data['soft_skills_text'] ?? ''));
    }

    private function replaceExperiences(CvProfile $cvProfile, array $experiences): void
    {
        $cvProfile->experiences()->delete();

        foreach ($experiences as $index => $experience) {
            $dates = $this->periodDates($experience['period'] ?? null, true);

            $cvProfile->experiences()->create([
                'position' => $experience['position'] ?: 'Puesto por revisar',
                'company' => $experience['company'] ?: 'Empresa por revisar',
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
                'is_current' => $dates['is_current'],
                'description' => $experience['description'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function replaceEducation(CvProfile $cvProfile, array $educationItems): void
    {
        $cvProfile->education()->delete();

        foreach ($educationItems as $index => $education) {
            $dates = $this->periodDates($education['period'] ?? null, false);

            $cvProfile->education()->create([
                'degree' => $education['degree'] ?: 'Estudio por revisar',
                'institution' => $education['institution'] ?: 'Institucion por revisar',
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
                'description' => $education['description'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    private function replaceSkills(CvProfile $cvProfile, string $type, array $items): void
    {
        $cvProfile->skills()->where('type', $type)->delete();

        foreach ($items as $index => $item) {
            $cvProfile->skills()->create([
                'name' => $item,
                'type' => $type,
                'category' => $type === 'language' ? 'Idioma' : null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, array<string, string|null>>
     */
    private function parseBlocks(?string $text, array $keys): array
    {
        $blocks = preg_split("/\n{2,}/u", trim((string) $text)) ?: [];

        return collect($blocks)
            ->map(fn ($block) => trim($block))
            ->filter()
            ->map(function ($block) use ($keys) {
                $lines = collect(preg_split('/\R/u', $block) ?: [])
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->values();
                $header = $lines->shift() ?? '';
                $parts = array_pad(array_map('trim', explode('|', $header, 3)), 3, null);

                return [
                    $keys[0] => $parts[0] ?: null,
                    $keys[1] => $parts[1] ?: null,
                    'period' => $parts[2] ?: null,
                    'description' => $lines->implode("\n") ?: null,
                ];
            })
            ->filter(fn ($item) => filled($item[$keys[0]]) || filled($item[$keys[1]]))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function parseList(?string $text): array
    {
        return collect(preg_split('/[\n,;]+/u', (string) $text) ?: [])
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function periodFromDates(?string $startYear, ?string $endYear, bool $isCurrent): ?string
    {
        if (! $startYear && ! $endYear && ! $isCurrent) {
            return null;
        }

        return trim(($startYear ?: '').' - '.($isCurrent ? 'presente' : ($endYear ?: '')));
    }

    /**
     * @return array{start_date: ?string, end_date: ?string, is_current: bool}
     */
    private function periodDates(?string $period, bool $requiresStartDate): array
    {
        preg_match_all('/(?:19|20)\d{2}/', $period ?? '', $matches);
        $years = $matches[0] ?? [];
        $isCurrent = (bool) preg_match('/actual|presente|present/i', $period ?? '');

        return [
            'start_date' => isset($years[0]) ? "{$years[0]}-01-01" : ($requiresStartDate ? now()->startOfYear()->toDateString() : null),
            'end_date' => (! $isCurrent && isset($years[1])) ? "{$years[1]}-12-31" : null,
            'is_current' => $isCurrent,
        ];
    }

    private function splitList(?string $value): ?array
    {
        if (! filled($value)) {
            return null;
        }

        return str($value)
            ->replace(["\r\n", "\r"], "\n")
            ->replace("\n", ',')
            ->explode(',')
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }
}
