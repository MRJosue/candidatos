<?php

namespace App\Http\Controllers;

use App\Models\CvProfile;
use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicTalentProfileController extends Controller
{
    public function edit(Talent $talent)
    {
        return view('public-talents.edit', [
            'talent' => $talent->load('cvProfile'),
            'profile' => $talent->cvProfile ?: new CvProfile([
                'title' => 'CV '.$talent->full_name,
                'full_name' => $talent->full_name,
                'email' => $talent->email,
                'phone' => $talent->phone,
                'location' => $talent->location,
                'headline' => $talent->headline ?: $talent->target_position,
                'summary' => $talent->technical_summary,
            ]),
        ]);
    }

    public function update(Request $request, Talent $talent)
    {
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
            'cv_title' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'objective' => ['nullable', 'string', 'max:2000'],
            'skills_section_title' => ['nullable', 'string', 'max:120'],
            'soft_skills_section_title' => ['nullable', 'string', 'max:120'],
            'awards' => ['nullable', 'string', 'max:2000'],
            'leadership_activities' => ['nullable', 'string', 'max:2000'],
            'interests' => ['nullable', 'string', 'max:1000'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
        ]);

        DB::transaction(function () use ($talent, $data): void {
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
            ]);

            $profile = $talent->cvProfile ?: $talent->cvProfile()->make([
                'user_id' => $talent->recruiter_id,
                'section_order' => CvProfile::defaultSectionOrder(),
            ]);

            $profile->fill([
                'user_id' => $talent->recruiter_id,
                'title' => $data['cv_title'],
                'full_name' => $talent->full_name,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'location' => $data['location'] ?? null,
                'headline' => $data['headline'] ?? null,
                'tagline' => $data['tagline'] ?? null,
                'summary' => $data['summary'] ?? null,
                'objective' => $data['objective'] ?? null,
                'skills_section_title' => $data['skills_section_title'] ?? 'Habilidades',
                'soft_skills_section_title' => $data['soft_skills_section_title'] ?? 'Habilidades blandas',
                'awards' => $data['awards'] ?? null,
                'leadership_activities' => $data['leadership_activities'] ?? null,
                'interests' => $data['interests'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'portfolio_url' => $data['portfolio_url'] ?? null,
            ]);

            $profile->save();
        });

        return redirect()
            ->route('public-talents.edit', ['talent' => $talent->public_token])
            ->with('status', 'Informacion actualizada.');
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
