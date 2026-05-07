<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Vacancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VacancyController extends Controller
{
    public function index(Request $request)
    {
        return view('vacancies.index', [
            'vacancies' => $request->user()
                ->vacancies()
                ->with(['company', 'position'])
                ->withCount('applications')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(Request $request)
    {
        return view('vacancies.create', [
            'vacancy' => new Vacancy([
                'status' => 'open',
                'currency' => 'MXN',
                'opened_at' => now(),
            ]),
            'companies' => $this->companyCatalog($request),
            'position' => new Position([
                'currency' => 'MXN',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        [$company, $position] = $this->syncCompanyAndPosition($request, $data);

        $vacancy = $request->user()->vacancies()->create([
            'company_id' => $company->id,
            'position_id' => $position->id,
            'title' => $position->title,
            'client_company' => $company->name,
            'location' => $position->location,
            'work_mode' => $position->work_mode,
            'employment_type' => $position->employment_type,
            'seniority' => $position->seniority,
            'status' => $data['status'],
            'salary_min' => $position->salary_min,
            'salary_max' => $position->salary_max,
            'currency' => $position->currency,
            'technical_stack' => $position->technical_stack,
            'description' => $position->description,
            'requirements' => $position->requirements,
            'opened_at' => $data['opened_at'] ?? now(),
            'closed_at' => $data['closed_at'] ?? null,
        ]);

        return redirect()->route('vacancies.show', $vacancy)->with('status', 'Vacante creada.');
    }

    public function show(Request $request, Vacancy $vacancy)
    {
        abort_unless($vacancy->recruiter_id === $request->user()->id, 403);

        return view('vacancies.show', [
            'vacancy' => $vacancy->load(['company', 'position', 'applications.talent', 'applications.cvProfile']),
        ]);
    }

    public function edit(Request $request, Vacancy $vacancy)
    {
        abort_unless($vacancy->recruiter_id === $request->user()->id, 403);

        return view('vacancies.edit', [
            'vacancy' => $vacancy->load(['company', 'position']),
            'companies' => $this->companyCatalog($request),
            'position' => $vacancy->position ?? new Position([
                'title' => $vacancy->title,
                'seniority' => $vacancy->seniority,
                'employment_type' => $vacancy->employment_type,
                'work_mode' => $vacancy->work_mode,
                'location' => $vacancy->location,
                'salary_min' => $vacancy->salary_min,
                'salary_max' => $vacancy->salary_max,
                'currency' => $vacancy->currency,
                'technical_stack' => $vacancy->technical_stack,
                'description' => $vacancy->description,
                'requirements' => $vacancy->requirements,
            ]),
        ]);
    }

    public function update(Request $request, Vacancy $vacancy)
    {
        abort_unless($vacancy->recruiter_id === $request->user()->id, 403);

        $data = $this->validatedData($request);
        [$company, $position] = $this->syncCompanyAndPosition($request, $data, $vacancy);

        $vacancy->update([
            'company_id' => $company->id,
            'position_id' => $position->id,
            'title' => $position->title,
            'client_company' => $company->name,
            'location' => $position->location,
            'work_mode' => $position->work_mode,
            'employment_type' => $position->employment_type,
            'seniority' => $position->seniority,
            'status' => $data['status'],
            'salary_min' => $position->salary_min,
            'salary_max' => $position->salary_max,
            'currency' => $position->currency,
            'technical_stack' => $position->technical_stack,
            'description' => $position->description,
            'requirements' => $position->requirements,
            'opened_at' => $data['opened_at'] ?? $vacancy->opened_at,
            'closed_at' => $data['closed_at'] ?? null,
        ]);

        return redirect()->route('vacancies.show', $vacancy)->with('status', 'Vacante actualizada.');
    }

    public function destroy(Request $request, Vacancy $vacancy)
    {
        abort_unless($vacancy->recruiter_id === $request->user()->id, 403);

        $vacancy->delete();

        return redirect()->route('vacancies.index')->with('status', 'Vacante eliminada.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'company_id' => [
                'required',
                Rule::exists('companies', 'id')->where('recruiter_id', $request->user()->id),
            ],
            'position_title' => ['required', 'string', 'max:180'],
            'position_department' => ['nullable', 'string', 'max:120'],
            'seniority' => ['nullable', 'string', 'max:80'],
            'employment_type' => ['nullable', 'string', 'max:80'],
            'work_mode' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:160'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'technical_stack' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['open', 'paused', 'closed', 'filled', 'cancelled'])],
            'opened_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
        ]);

        $data['technical_stack'] = $this->splitList($data['technical_stack'] ?? null);

        return $data;
    }

    private function syncCompanyAndPosition(Request $request, array $data, ?Vacancy $vacancy = null): array
    {
        $company = $request->user()->companies()->findOrFail($data['company_id']);

        $position = $vacancy?->position ?? new Position(['recruiter_id' => $request->user()->id]);
        $position->fill([
            'recruiter_id' => $request->user()->id,
            'company_id' => $company->id,
            'title' => $data['position_title'],
            'department' => $data['position_department'] ?? null,
            'seniority' => $data['seniority'] ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'work_mode' => $data['work_mode'] ?? null,
            'location' => $data['location'] ?? null,
            'salary_min' => $data['salary_min'] ?? null,
            'salary_max' => $data['salary_max'] ?? null,
            'currency' => $data['currency'],
            'technical_stack' => $data['technical_stack'],
            'description' => $data['description'] ?? null,
            'requirements' => $data['requirements'] ?? null,
        ])->save();

        return [$company, $position];
    }

    private function companyCatalog(Request $request)
    {
        return $request->user()
            ->companies()
            ->orderBy('name')
            ->get();
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
