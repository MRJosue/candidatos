<?php

namespace App\Http\Controllers;

use App\Models\CvProfile;
use App\Models\JobApplication;
use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        return view('applications.index', [
            'applications' => $request->user()
                ->jobApplications()
                ->with(['talent', 'vacancy.company', 'vacancy.position', 'cvProfile'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        return view('applications.create', [
            'application' => new JobApplication([
                'status' => 'applied',
                'stage' => JobApplication::DEFAULT_STAGE,
                'applied_at' => now(),
                'last_activity_at' => now(),
            ]),
            ...$this->formOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $application = JobApplication::create([
            ...$data,
            'recruiter_id' => $request->user()->id,
        ]);

        return redirect()->route('applications.show', $application)->with('status', 'Postulacion creada.');
    }

    public function show(Request $request, JobApplication $application)
    {
        abort_unless($application->recruiter_id === $request->user()->id, 403);

        return view('applications.show', [
            'application' => $application->load(['talent', 'vacancy.company', 'vacancy.position', 'cvProfile']),
        ]);
    }

    public function edit(Request $request, JobApplication $application)
    {
        abort_unless($application->recruiter_id === $request->user()->id, 403);

        return view('applications.edit', [
            'application' => $application,
            ...$this->formOptions($request),
        ]);
    }

    public function update(Request $request, JobApplication $application)
    {
        abort_unless($application->recruiter_id === $request->user()->id, 403);

        $application->update($this->validatedData($request, $application));

        return redirect()->route('applications.show', $application)->with('status', 'Postulacion actualizada.');
    }

    public function destroy(Request $request, JobApplication $application)
    {
        abort_unless($application->recruiter_id === $request->user()->id, 403);

        $application->delete();

        return redirect()->route('applications.index')->with('status', 'Postulacion eliminada.');
    }

    public function storeForTalent(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        $data = $request->validate([
            'vacancy_id' => [
                'required',
                Rule::exists('vacancies', 'id')->where('recruiter_id', $request->user()->id),
                Rule::unique('job_applications', 'vacancy_id')->where('talent_id', $talent->id),
            ],
        ]);

        JobApplication::create([
            'recruiter_id' => $request->user()->id,
            'talent_id' => $talent->id,
            'vacancy_id' => $data['vacancy_id'],
            'cv_profile_id' => $talent->cvProfile?->id,
            'status' => 'applied',
            'stage' => JobApplication::DEFAULT_STAGE,
            'applied_at' => now(),
            'last_activity_at' => now(),
        ]);

        return redirect()->route('talents.index')->with('status', 'Postulacion creada.');
    }

    private function validatedData(Request $request, ?JobApplication $application = null): array
    {
        return $request->validate([
            'talent_id' => [
                'required',
                Rule::exists('talents', 'id')->where('recruiter_id', $request->user()->id),
            ],
            'vacancy_id' => [
                'required',
                Rule::exists('vacancies', 'id')->where('recruiter_id', $request->user()->id),
                Rule::unique('job_applications', 'vacancy_id')
                    ->where('talent_id', $request->input('talent_id'))
                    ->ignore($application),
            ],
            'cv_profile_id' => [
                'nullable',
                Rule::exists('cv_profiles', 'id')->where('user_id', $request->user()->id),
            ],
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
            'stage' => ['required', Rule::in(array_keys($this->stageOptions()))],
            'match_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'applied_at' => ['nullable', 'date'],
            'last_activity_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function formOptions(Request $request): array
    {
        return [
            'talents' => $request->user()
                ->talents()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'vacancies' => $request->user()
                ->vacancies()
                ->with(['company', 'position'])
                ->orderBy('title')
                ->get(),
            'cvProfiles' => CvProfile::query()
                ->where('user_id', $request->user()->id)
                ->orderBy('title')
                ->get(),
            'statusOptions' => $this->statusOptions(),
            'stageOptions' => $this->stageOptions(),
        ];
    }

    private function statusOptions(): array
    {
        return [
            'applied' => 'Aplicada',
            'active' => 'Activa',
            'rejected' => 'Rechazada',
            'withdrawn' => 'Retirada',
            'hired' => 'Contratada',
        ];
    }

    private function stageOptions(): array
    {
        return JobApplication::stageOptions();
    }
}
