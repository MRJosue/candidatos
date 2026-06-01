<?php

namespace App\Http\Controllers;

use App\Models\Talent;
use App\Models\CvTemplate;
use App\Services\CvWordDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use ZipArchive;

class TalentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'created_date' => ['nullable', 'string', 'max:40'],
        ]);

        $name = trim((string) ($filters['name'] ?? ''));
        $createdDate = trim((string) ($filters['created_date'] ?? ''));
        $normalizedCreatedDate = $this->normalizedDateSearch($createdDate);
        $visibleRecruiterIds = $request->user()->visibleRecruiterUserIds();

        return view('talents.index', [
            'talents' => Talent::query()
                ->whereIn('recruiter_id', $visibleRecruiterIds)
                ->with(['cvProfile', 'cvProfiles', 'applications:id,talent_id,vacancy_id'])
                ->withCount('applications')
                ->when($name !== '', function ($query) use ($name): void {
                    collect(preg_split('/\s+/', $name) ?: [])
                        ->filter()
                        ->each(function (string $term) use ($query): void {
                            $query->where(function ($query) use ($term): void {
                                $query->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('last_name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            });
                        });
                })
                ->when($createdDate !== '', function ($query) use ($createdDate, $normalizedCreatedDate): void {
                    $query->whereDate('created_at', 'like', '%'.($normalizedCreatedDate ?: $createdDate).'%');
                })
                ->orderByDesc('id')
                ->paginate(15)
                ->appends($request->query()),
            'vacancies' => $request->user()
                ->vacancies()
                ->with(['company', 'position'])
                ->whereIn('status', ['open', 'paused'])
                ->orderBy('title')
                ->get(),
            'filters' => [
                'name' => $name,
                'created_date' => $createdDate,
            ],
            'filterOptions' => [
                'createdDates' => Talent::query()
                    ->whereIn('recruiter_id', $visibleRecruiterIds)
                    ->selectRaw('DATE(created_at) as created_date')
                    ->whereNotNull('created_at')
                    ->distinct()
                    ->orderByDesc('created_date')
                    ->pluck('created_date'),
            ],
        ]);
    }

    public function create()
    {
        return view('talents.create', [
            'talent' => new Talent([
                'status' => 'active',
                'currency' => 'MXN',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $talent = $request->user()->talents()->create($this->validatedData($request));

        return redirect()->route('talents.show', $talent)->with('status', 'Postulante creado.');
    }

    public function show(Request $request, Talent $talent)
    {
        abort_unless($request->user()->canViewRecruiterOwner($talent->recruiter_id), 403);

        return view('talents.show', [
            'talent' => $talent->load([
                'cvProfile.template',
                'cvProfiles.template',
                'applications.vacancy.company',
                'applications.vacancy.position',
            ]),
        ]);
    }

    public function edit(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        return view('talents.edit', compact('talent'));
    }

    public function update(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        $talent->update($this->validatedData($request));

        return redirect()->route('talents.show', $talent)->with('status', 'Postulante actualizado.');
    }

    public function destroy(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        $talent->delete();

        return redirect()->route('talents.index')->with('status', 'Postulante eliminado.');
    }

    public function downloadCvs(Request $request, CvWordDocumentService $wordDocumentService)
    {
        $data = $request->validate([
            'talent_ids' => ['required', 'array', 'min:1'],
            'talent_ids.*' => ['integer'],
            'cv_template_slug' => ['nullable', Rule::in(['act-digital', 'academico-bullet'])],
            'cv_language' => ['nullable', Rule::in(array_keys(\App\Models\CvProfile::languageOptions()))],
            'file_format' => ['nullable', Rule::in(['pdf', 'word'])],
        ]);

        CvTemplate::ensureDefaultTemplates();

        $templateSlug = $data['cv_template_slug'] ?? 'act-digital';
        $fileFormat = $data['file_format'] ?? 'pdf';
        $template = CvTemplate::query()
            ->where('slug', $templateSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $talents = Talent::query()
            ->whereIn('recruiter_id', $request->user()->visibleRecruiterUserIds())
            ->with(['cvProfiles.template', 'cvProfiles.experiences', 'cvProfiles.education', 'cvProfiles.skills'])
            ->whereIn('id', $data['talent_ids'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $language = $data['cv_language'] ?? null;
        $profiles = $talents
            ->map(fn (Talent $talent) => $this->profileForLanguage($talent, $language))
            ->filter();

        if ($profiles->isEmpty()) {
            return back()->withErrors(['talent_ids' => 'Selecciona al menos un talento con CV asociado en el idioma elegido.']);
        }

        $directory = storage_path('app/private/bulk-cv-downloads');
        File::ensureDirectoryExists($directory);

        $zipPath = $directory.'/cvs-'.now()->format('Ymd-His').'-'.str()->random(8).'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors(['talent_ids' => 'No se pudo preparar el archivo de descarga.']);
        }

        $usedNames = [];

        foreach ($profiles as $profile) {
            $profile->setRelation('template', $template);

            $paper = $template->slug === 'act-digital' ? 'a4' : 'letter';
            $baseName = str($profile->title ?: $profile->full_name)->slug()->value() ?: 'cv-'.$profile->id;
            $extension = $fileFormat === 'word' ? 'docx' : 'pdf';
            $fileName = $baseName.'.'.$extension;
            $counter = 2;

            while (in_array($fileName, $usedNames, true)) {
                $fileName = $baseName.'-'.$counter.'.'.$extension;
                $counter++;
            }

            $usedNames[] = $fileName;

            $contents = $fileFormat === 'word'
                ? $wordDocumentService->output($profile)
                : Pdf::loadView('cv.pdf', ['profile' => $profile])
                    ->setPaper($paper)
                    ->output();

            $zip->addFromString($fileName, $contents);
        }

        $zip->close();

        return response()
            ->download($zipPath, 'cvs-talentos-'.$fileFormat.'-'.now()->format('Ymd-His').'.zip')
            ->deleteFileAfterSend(true);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:160'],
            'headline' => ['nullable', 'string', 'max:180'],
            'target_position' => ['nullable', 'string', 'max:160'],
            'seniority' => ['nullable', 'string', 'max:80'],
            'source' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'hired', 'rejected', 'paused'])],
            'availability' => ['nullable', 'string', 'max:120'],
            'salary_expectation_min' => ['nullable', 'integer', 'min:0'],
            'salary_expectation_max' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'technical_stack' => ['nullable', 'string', 'max:1000'],
            'languages' => ['nullable', 'string', 'max:1000'],
            'links' => ['nullable', 'string', 'max:1000'],
            'technical_summary' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'last_contacted_at' => ['nullable', 'date'],
        ]);

        $data['status'] = $data['status'] ?? 'active';
        $data['currency'] = strtoupper($data['currency'] ?? 'MXN');
        $data['technical_stack'] = $this->splitList($data['technical_stack'] ?? null);
        $data['languages'] = $this->splitList($data['languages'] ?? null);
        $data['links'] = $this->splitList($data['links'] ?? null);

        return $data;
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

    private function normalizedDateSearch(string $value): ?string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        return null;
    }

    private function profileForLanguage(Talent $talent, ?string $language): ?\App\Models\CvProfile
    {
        if (! $language) {
            return $talent->cvProfiles->first();
        }

        return $talent->cvProfiles
            ->first(fn ($profile) => ($profile->language ?: 'es') === $language);
    }
}
