<?php

namespace App\Http\Controllers;

use App\Models\CvProfile;
use App\Models\JobApplication;
use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $perPage = $this->perPage($request);

        return view('applications.index', [
            'applications' => $this->filteredApplicationsQuery($request, $filters)
                ->latest()
                ->paginate($perPage)
                ->appends($request->query()),
            'filterOptions' => $this->filterOptions($request),
            'filters' => $filters,
            'perPage' => $perPage,
            'perPageOptions' => $this->perPageOptions(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $applications = $this->filteredApplicationsQuery($request, $filters)
            ->latest()
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Postulaciones');

        $headers = [
            'Postulante',
            'Email',
            'Vacante',
            'Compania',
            'Estado',
            'Etapa',
            'Match',
            'Fecha de postulacion',
            'Ultima actividad',
            'CV asociado',
            'Notas',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($applications as $application) {
            $sheet->fromArray([
                $application->talent->full_name,
                $application->talent->email,
                $application->vacancy->display_title,
                $application->vacancy->display_company ?? 'Cliente confidencial',
                $application->status_label,
                JobApplication::stageLabelFor($application->stage),
                $application->match_score !== null ? "{$application->match_score}%" : 'Sin score',
                $application->applied_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                $application->last_activity_at?->format('d/m/Y H:i') ?? 'Sin actividad',
                $application->cvProfile?->title ?? 'Sin CV asociado',
                $application->notes,
            ], null, "A{$row}");
            $row++;
        }

        $highestColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '111827']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9CA3AF']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A1:{$highestColumn}".max(1, $row - 1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$highestColumn}1");

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setWidth(match ($columnIndex) {
                1, 3, 4, 10 => 28,
                11 => 45,
                default => 18,
            });
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'postulaciones.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
        return JobApplication::statusOptions();
    }

    private function stageOptions(): array
    {
        return JobApplication::stageOptions();
    }

    private function filteredApplicationsQuery(Request $request, array $filters)
    {
        return $request->user()
            ->jobApplications()
            ->with(['talent', 'vacancy.company', 'vacancy.position', 'cvProfile'])
            ->when($filters['talent_id'], fn ($query, string $talentId) => $query->where('talent_id', $talentId))
            ->when($filters['vacancy_id'], fn ($query, string $vacancyId) => $query->where('vacancy_id', $vacancyId))
            ->when($filters['status'], fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['stage'], function ($query, string $stage): void {
                $query->whereIn('stage', $this->stageValuesForFilter($stage));
            })
            ->when($filters['match_score'] !== null, fn ($query) => $query->where('match_score', $filters['match_score']))
            ->when($filters['last_activity_date'], fn ($query, string $date) => $query->whereDate('last_activity_at', $date));
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'talent_id' => ['nullable', 'integer'],
            'vacancy_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(array_keys($this->statusOptions()))],
            'stage' => ['nullable', Rule::in(array_keys($this->stageOptions()))],
            'match_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'last_activity_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return [
            'talent_id' => $validated['talent_id'] ?? null,
            'vacancy_id' => $validated['vacancy_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'stage' => $validated['stage'] ?? null,
            'match_score' => array_key_exists('match_score', $validated) ? $validated['match_score'] : null,
            'last_activity_date' => $validated['last_activity_date'] ?? null,
        ];
    }

    private function filterOptions(Request $request): array
    {
        $applications = $request->user()
            ->jobApplications()
            ->with(['talent', 'vacancy.company', 'vacancy.position'])
            ->get();

        return [
            'talents' => $applications
                ->map(fn (JobApplication $application): array => [
                    'id' => $application->talent_id,
                    'label' => $application->talent->full_name,
                ])
                ->unique('id')
                ->sortBy('label')
                ->values(),
            'vacancies' => $applications
                ->map(fn (JobApplication $application): array => [
                    'id' => $application->vacancy_id,
                    'label' => $application->vacancy->display_title,
                ])
                ->unique('id')
                ->sortBy('label')
                ->values(),
            'statuses' => $this->statusOptions(),
            'stages' => $this->stageOptions(),
            'matchScores' => $applications
                ->pluck('match_score')
                ->filter(fn (?int $score) => $score !== null)
                ->unique()
                ->sort()
                ->values(),
            'lastActivityDates' => $applications
                ->pluck('last_activity_at')
                ->filter()
                ->map(fn ($date) => $date->format('Y-m-d'))
                ->unique()
                ->sort()
                ->values(),
        ];
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 15);

        return in_array($perPage, $this->perPageOptions(), true) ? $perPage : 15;
    }

    private function perPageOptions(): array
    {
        return [10, 15, 25, 50, 100];
    }

    private function stageValuesForFilter(string $stage): array
    {
        $legacyValues = collect(JobApplication::LEGACY_STAGE_MAP)
            ->filter(fn (string $normalizedStage) => $normalizedStage === $stage)
            ->keys()
            ->all();

        return [$stage, ...$legacyValues];
    }
}
