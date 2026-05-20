<?php

namespace App\Http\Controllers;

use App\Models\Talent;
use App\Models\CvTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use ZipArchive;

class TalentController extends Controller
{
    public function index(Request $request)
    {
        return view('talents.index', [
            'talents' => $request->user()
                ->talents()
                ->with(['cvProfile', 'applications:id,talent_id,vacancy_id'])
                ->withCount('applications')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(15),
            'vacancies' => $request->user()
                ->vacancies()
                ->with(['company', 'position'])
                ->whereIn('status', ['open', 'paused'])
                ->orderBy('title')
                ->get(),
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
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        return view('talents.show', [
            'talent' => $talent->load([
                'cvProfile.template',
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

    public function downloadCvs(Request $request)
    {
        $data = $request->validate([
            'talent_ids' => ['required', 'array', 'min:1'],
            'talent_ids.*' => ['integer'],
            'cv_template_slug' => ['nullable', Rule::in(['act-digital', 'academico-bullet'])],
        ]);

        CvTemplate::ensureDefaultTemplates();

        $templateSlug = $data['cv_template_slug'] ?? 'act-digital';
        $template = CvTemplate::query()
            ->where('slug', $templateSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $talents = $request->user()
            ->talents()
            ->with(['cvProfile.template', 'cvProfile.experiences', 'cvProfile.education', 'cvProfile.skills'])
            ->whereIn('id', $data['talent_ids'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $profiles = $talents->pluck('cvProfile')->filter();

        if ($profiles->isEmpty()) {
            return back()->withErrors(['talent_ids' => 'Selecciona al menos un talento con CV asociado.']);
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
            $fileName = $baseName.'.pdf';
            $counter = 2;

            while (in_array($fileName, $usedNames, true)) {
                $fileName = $baseName.'-'.$counter.'.pdf';
                $counter++;
            }

            $usedNames[] = $fileName;

            $zip->addFromString(
                $fileName,
                Pdf::loadView('cv.pdf', ['profile' => $profile])
                    ->setPaper($paper)
                    ->output()
            );
        }

        $zip->close();

        return response()
            ->download($zipPath, 'cvs-talentos-'.now()->format('Ymd-His').'.zip')
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
}
