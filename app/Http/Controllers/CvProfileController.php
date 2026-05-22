<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvProfileRequest;
use App\Http\Requests\UpdateCvProfileRequest;
use App\Models\CvProfile;
use App\Models\CvTemplate;
use App\Models\Talent;
use App\Services\CvAiDocumentImportService;
use App\Services\CvDocumentImportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CvProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cv.index', [
            'profiles' => auth()->user()->cvProfiles()->with(['template', 'talent'])->latest()->paginate(12),
            'talents' => auth()->user()->talents()->with('cvProfile:id,talent_id,title')->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $documentImport = session($this->createDocumentImportSessionKey());

        return view('cv.create', [
            'profile' => $this->profileWithImportDefaults(new CvProfile([
                'cv_template_id' => CvTemplate::defaultTemplate()?->id,
            ]), $documentImport),
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
            'documentImport' => $documentImport,
            'sectionText' => $this->sectionTextFromImport($documentImport),
        ]);
    }

    public function createForTalent(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        if ($talent->cvProfile) {
            return redirect()->route('cv.edit', $talent->cvProfile);
        }

        $documentImport = session($this->createDocumentImportSessionKey());

        return view('cv.create', [
            'profile' => $this->profileWithImportDefaults(new CvProfile([
                'talent_id' => $talent->id,
                'title' => 'CV '.$talent->full_name,
                'full_name' => $talent->full_name,
                'cv_template_id' => CvTemplate::defaultTemplate()?->id,
            ]), $documentImport),
            'talent' => $talent,
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
            'documentImport' => $documentImport,
            'sectionText' => $this->sectionTextFromImport($documentImport),
        ]);
    }

    public function storeForTalent(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        if ($talent->cvProfile) {
            return redirect()
                ->route('cv.edit', $talent->cvProfile)
                ->with('status', 'Este talento ya tiene un CV asociado.');
        }

        $profile = $request->user()->cvProfiles()->create($this->profileDataFromTalent($talent));

        return redirect()
            ->route('cv.edit', $profile)
            ->with('status', 'CV creado desde el talento. Puedes completarlo antes de descargarlo.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCvProfileRequest $request)
    {
        $talent = $this->validatedTalent($request);
        $data = $request->validated();

        $sectionData = $this->validatedSectionText($request);

        $profile = DB::transaction(function () use ($request, $talent, $data, $sectionData): CvProfile {
            $profile = $request->user()->cvProfiles()->create([
                ...$this->profileDataForStorage($data),
                'talent_id' => $talent?->id,
                'cv_template_id' => $data['cv_template_id'] ?? CvTemplate::defaultTemplate()?->id,
                'section_order' => CvProfile::defaultSectionOrder(),
            ]);

            $import = session($this->createDocumentImportSessionKey());

            if ($import) {
                if ($request->boolean('apply_document_import')) {
                    $this->applyImportedData($profile, $import['parsed'] ?? [], $this->validatedImportApplyOptions($request));
                } elseif ($this->hasSectionTextInput($request)) {
                    $this->replaceSectionsFromText($profile, $sectionData);
                }

                session()->forget($this->createDocumentImportSessionKey());
            } elseif ($this->hasSectionTextInput($request)) {
                $this->replaceSectionsFromText($profile, $sectionData);
            }

            return $profile;
        });

        return redirect()
            ->route('talents.index')
            ->with('status', 'CV creado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        return view('cv.show', [
            'profile' => $cvProfile->load(['template', 'experiences', 'education', 'skills']),
            'templates' => CvTemplate::where('is_active', true)->orderBy('is_premium')->orderBy('name')->get(),
            'purchasedTemplateIds' => auth()->user()?->purchases()->where('status', 'paid')->pluck('cv_template_id')->all() ?? [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        return view('cv.edit', [
            'profile' => $cvProfile->load(['experiences', 'education', 'skills']),
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
            'documentImport' => session($this->documentImportSessionKey($cvProfile)),
            'sectionText' => $this->sectionText($cvProfile),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCvProfileRequest $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $talent = $this->validatedTalent($request);

        $sectionData = $this->validatedSectionText($request);

        DB::transaction(function () use ($cvProfile, $request, $talent, $sectionData): void {
            $cvProfile->update([
                ...$this->profileDataForStorage($request->validated()),
                'talent_id' => $talent?->id,
            ]);

            if ($this->hasSectionTextInput($request)) {
                $this->replaceSectionsFromText($cvProfile, $sectionData);
            }
        });

        return redirect()
            ->route('talents.index')
            ->with('status', 'CV actualizado.');
    }

    public function assignTalent(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validate([
            'talent_id' => ['nullable', 'integer', 'exists:talents,id'],
        ]);

        $talentId = $data['talent_id'] ?? null;
        $talent = null;

        if ($talentId) {
            $talent = $request->user()->talents()->findOrFail($talentId);
        }

        if ($talent && $talent->cvProfile()->whereKeyNot($cvProfile->id)->exists()) {
            return back()->withErrors(['talent_id' => 'Este postulante ya tiene un CV asociado.']);
        }

        DB::transaction(function () use ($cvProfile, $talent): void {
            $cvProfile->update(['talent_id' => $talent?->id]);
        });

        return redirect()->route('cv.index')->with('status', 'CV asignado al postulante.');
    }

    public function updateTemplate(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validate([
            'cv_template_id' => [
                'required',
                Rule::exists('cv_templates', 'id')->where('is_active', true),
            ],
        ]);

        $template = CvTemplate::findOrFail($data['cv_template_id']);

        $hasPurchase = $template
            ? $request->user()->purchases()
                ->where('cv_template_id', $template->id)
                ->where('status', 'paid')
                ->exists()
            : false;

        abort_if($template?->is_premium && ! $hasPurchase, 403, 'Compra esta plantilla para asignarla a tu CV.');

        $cvProfile->update(['cv_template_id' => $template?->id]);

        return redirect()->route('cv.show', $cvProfile)->with('status', 'Tipo de CV actualizado para impresion.');
    }

    public function updateSectionOrder(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validate([
            'side' => ['required', 'array'],
            'side.*' => ['required', 'string', 'in:skills,languages,soft_skills'],
            'main' => ['required', 'array'],
            'main.*' => ['required', 'string', 'in:experiences,education'],
        ]);

        $data = [
            'side' => CvProfile::normalizeSectionOrder($data['side'], CvProfile::SIDE_SECTIONS),
            'main' => CvProfile::normalizeSectionOrder($data['main'], CvProfile::MAIN_SECTIONS),
        ];

        $cvProfile->update(['section_order' => $data]);

        return response()->json([
            'message' => 'Orden de secciones actualizado.',
            'section_order' => $data,
        ]);
    }

    public function importDocumentWithAi(
        Request $request,
        CvProfile $cvProfile,
        CvDocumentImportService $importService,
        CvAiDocumentImportService $aiImportService,
    ) {
        $this->authorize('update', $cvProfile);

        $import = $this->analyzeDocumentImport($request, $importService, $aiImportService);

        if ($import instanceof RedirectResponse) {
            return $import;
        }

        session()->put($this->documentImportSessionKey($cvProfile), $import);

        return redirect()
            ->route('cv.edit', $cvProfile)
            ->with('status', 'Analisis con IA listo. Revisa la previsualizacion antes de aplicar cambios.');
    }

    public function importDocumentForCreateWithAi(
        Request $request,
        CvDocumentImportService $importService,
        CvAiDocumentImportService $aiImportService,
    ) {
        $import = $this->analyzeDocumentImport($request, $importService, $aiImportService);

        if ($import instanceof RedirectResponse) {
            return $import;
        }

        session()->put($this->createDocumentImportSessionKey(), $import);

        $talent = filled($request->input('talent_id'))
            ? $request->user()->talents()->find($request->integer('talent_id'))
            : null;

        return redirect()
            ->route($talent ? 'talents.cv.create' : 'cv.create', $talent ?: [])
            ->with('status', 'Analisis con IA listo. Revisa la previsualizacion antes de guardar el CV.');
    }

    public function updateSections(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $this->validatedSectionText($request);

        DB::transaction(fn () => $this->replaceSectionsFromText($cvProfile, $data));

        return redirect()
            ->route('cv.edit', $cvProfile)
            ->with('status', 'Secciones del CV actualizadas.');
    }

    public function applyDocumentImport(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $this->validatedImportApplyOptions($request);

        $import = session($this->documentImportSessionKey($cvProfile));

        if (! $import) {
            return redirect()
                ->route('cv.edit', $cvProfile)
                ->withErrors(['cv_document' => 'Primero carga un documento para revisar los datos detectados.']);
        }

        $parsed = $import['parsed'] ?? [];

        DB::transaction(fn () => $this->applyImportedData($cvProfile, $parsed, $data));

        session()->forget($this->documentImportSessionKey($cvProfile));

        return redirect()
            ->route('cv.edit', $cvProfile)
            ->with('status', 'Datos detectados aplicados al CV. Puedes ajustar cualquier campo antes de descargar.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CvProfile $cvProfile)
    {
        $this->authorize('delete', $cvProfile);

        $cvProfile->delete();

        return redirect()->route('cv.index')->with('status', 'CV eliminado.');
    }

    public function preview(CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        return view('cv.preview', [
            'profile' => $cvProfile->load(['template', 'experiences', 'education', 'skills']),
        ]);
    }

    public function download(CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        $profile = $cvProfile->load(['template', 'experiences', 'education', 'skills']);

        $paper = $profile->template?->slug === 'academico-bullet' ? 'letter' : 'a4';

        return Pdf::loadView('cv.pdf', compact('profile'))
            ->setPaper($paper)
            ->download(str($profile->title)->slug().'.pdf');
    }

    private function validatedTalent(Request $request): ?Talent
    {
        $data = $request->validate([
            'talent_id' => ['nullable', 'integer', 'exists:talents,id'],
        ]);

        if (! filled($data['talent_id'] ?? null)) {
            return null;
        }

        $talent = $request->user()->talents()->findOrFail($data['talent_id']);

        $existingCvQuery = $talent->cvProfile();

        if ($request->route('cvProfile')) {
            $existingCvQuery->whereKeyNot($request->route('cvProfile')->id);
        }

        if ($existingCvQuery->exists()) {
            throw ValidationException::withMessages([
                'talent_id' => 'Este postulante ya tiene un CV asociado.',
            ]);
        }

        return $talent;
    }

    /**
     * @return array<string, string|null>
     */
    private function validatedSectionText(Request $request): array
    {
        return $request->validate([
            'experiences_text' => ['nullable', 'string', 'max:12000'],
            'education_text' => ['nullable', 'string', 'max:8000'],
            'skills_text' => ['nullable', 'string', 'max:4000'],
            'languages_text' => ['nullable', 'string', 'max:4000'],
            'soft_skills_text' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function hasSectionTextInput(Request $request): bool
    {
        return $request->hasAny([
            'experiences_text',
            'education_text',
            'skills_text',
            'languages_text',
            'soft_skills_text',
        ]);
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function replaceSectionsFromText(CvProfile $cvProfile, array $data): void
    {
        $this->replaceExperiences($cvProfile, $this->parseExperienceBlocks($data['experiences_text'] ?? ''));
        $this->replaceEducation($cvProfile, $this->parseEducationBlocks($data['education_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'skill', $this->parseList($data['skills_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'language', $this->parseList($data['languages_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'soft_skill', $this->parseList($data['soft_skills_text'] ?? ''));
    }

    private function profileDataFromTalent(Talent $talent): array
    {
        return $this->profileDataForStorage([
            'talent_id' => $talent->id,
            'title' => 'CV '.$talent->full_name,
            'full_name' => $talent->full_name,
            'section_order' => CvProfile::defaultSectionOrder(),
            'cv_template_id' => CvTemplate::defaultTemplate()?->id,
        ]);
    }

    private function profileDataForStorage(array $data): array
    {
        $data['email'] = $data['email'] ?? '';

        return $data;
    }

    private function documentImportSessionKey(CvProfile $cvProfile): string
    {
        return "cv_document_import.{$cvProfile->id}";
    }

    private function createDocumentImportSessionKey(): string
    {
        return 'cv_document_import.create.'.auth()->id();
    }

    private function profileWithImportDefaults(CvProfile $profile, ?array $import): CvProfile
    {
        $profileData = $import['parsed']['profile'] ?? null;

        if (! is_array($profileData)) {
            return $profile;
        }

        $defaults = $this->profileImportData($profileData);
        $awards = $this->awardsTextFromImport($import['parsed']['awards'] ?? null);

        if (filled($awards)) {
            $defaults['awards'] = $awards;
        }

        if (filled($defaults['full_name'] ?? null) && blank($profile->title)) {
            $defaults['title'] = 'CV '.$defaults['full_name'];
        }

        foreach ($defaults as $key => $value) {
            if (blank($profile->{$key})) {
                $profile->{$key} = $value;
            }
        }

        return $profile;
    }

    private function analyzeDocumentImport(
        Request $request,
        CvDocumentImportService $importService,
        CvAiDocumentImportService $aiImportService,
    ): array|RedirectResponse {
        $data = $request->validate([
            'cv_document' => ['required', 'file', 'mimes:pdf,docx,txt', 'max:6144'],
        ]);

        try {
            $text = $importService->extractText($data['cv_document']);

            return [
                'original_name' => $data['cv_document']->getClientOriginalName(),
                'source' => 'ai',
                'parsed' => [
                    ...$aiImportService->analyze($text),
                    'raw_text' => str($text)->limit(12000, '')->toString(),
                ],
            ];
        } catch (RuntimeException $exception) {
            Log::warning('CV AI document import failed.', [
                'cv_profile_id' => $request->route('cvProfile')?->id,
                'user_id' => $request->user()?->id,
                'file_name' => $data['cv_document']->getClientOriginalName(),
                'file_mime' => $data['cv_document']->getClientMimeType(),
                'file_size' => $data['cv_document']->getSize(),
                'message' => $exception->getMessage(),
            ]);

            return back()
                ->withErrors(['cv_document_ai' => $exception->getMessage()])
                ->withInput();
        } catch (Throwable $exception) {
            Log::error('Unexpected CV AI document import error.', [
                'cv_profile_id' => $request->route('cvProfile')?->id,
                'user_id' => $request->user()?->id,
                'file_name' => $data['cv_document']->getClientOriginalName(),
                'file_mime' => $data['cv_document']->getClientMimeType(),
                'file_size' => $data['cv_document']->getSize(),
                'exception' => $exception,
            ]);

            return back()
                ->withErrors(['cv_document_ai' => 'No se pudo analizar el documento con IA. Intenta de nuevo con un PDF con texto real, DOCX o TXT.'])
                ->withInput();
        }
    }

    private function validatedImportApplyOptions(Request $request): array
    {
        return $request->validate([
            'apply_profile' => ['nullable', 'boolean'],
            'apply_experiences' => ['nullable', 'boolean'],
            'apply_education' => ['nullable', 'boolean'],
            'apply_skills' => ['nullable', 'boolean'],
            'apply_languages' => ['nullable', 'boolean'],
            'apply_soft_skills' => ['nullable', 'boolean'],
        ]);
    }

    private function applyImportedData(CvProfile $cvProfile, array $parsed, array $data): void
    {
        if ($data['apply_profile'] ?? false) {
            $profileData = $this->profileImportData($parsed['profile'] ?? []);
            $awards = $this->awardsTextFromImport($parsed['awards'] ?? null);

            if (filled($awards)) {
                $profileData['awards'] = $awards;
            }

            $cvProfile->update($profileData);
        }

        if ($data['apply_experiences'] ?? false) {
            $this->replaceExperiences($cvProfile, $parsed['experiences'] ?? []);
        }

        if ($data['apply_education'] ?? false) {
            $this->replaceEducation($cvProfile, $parsed['education'] ?? []);
        }

        if ($data['apply_skills'] ?? false) {
            $this->replaceSkills($cvProfile, 'skill', $parsed['skills'] ?? []);
        }

        if ($data['apply_languages'] ?? false) {
            $this->replaceSkills($cvProfile, 'language', $parsed['languages'] ?? []);
        }

        if ($data['apply_soft_skills'] ?? false) {
            $this->replaceSkills($cvProfile, 'soft_skill', $parsed['soft_skills'] ?? []);
        }
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
     * @return array<string, string>
     */
    private function sectionTextFromImport(?array $import): array
    {
        $parsed = $import['parsed'] ?? [];

        if (! is_array($parsed)) {
            return [];
        }

        return [
            'experiences' => collect($parsed['experiences'] ?? [])
                ->filter(fn ($experience) => is_array($experience))
                ->map(fn ($experience) => trim(implode("\n", array_filter([
                    implode(' | ', array_filter([
                        $experience['position'] ?? $experience['title'] ?? null,
                        $experience['company'] ?? $experience['organization'] ?? null,
                        $experience['period'] ?? null,
                    ])),
                    $experience['description'] ?? null,
                ]))))
                ->implode("\n\n"),
            'education' => collect($parsed['education'] ?? [])
                ->filter(fn ($education) => is_array($education))
                ->map(fn ($education) => trim(implode("\n", array_filter([
                    implode(' | ', array_filter([
                        $education['degree'] ?? $education['title'] ?? null,
                        $education['institution'] ?? $education['organization'] ?? null,
                        $education['period'] ?? null,
                    ])),
                    $education['description'] ?? null,
                ]))))
                ->implode("\n\n"),
            'skills' => collect($parsed['skills'] ?? [])->filter()->implode("\n"),
            'languages' => collect($parsed['languages'] ?? [])->filter()->implode("\n"),
            'soft_skills' => collect($parsed['soft_skills'] ?? [])->filter()->implode("\n"),
        ];
    }

    /**
     * @param  array<string, string|null>  $profile
     * @return array<string, string>
     */
    private function profileImportData(array $profile): array
    {
        return collect([
            'full_name' => $profile['full_name'] ?? null,
            'email' => $profile['email'] ?? null,
            'phone' => $profile['phone'] ?? null,
            'location' => $profile['location'] ?? null,
            'headline' => $profile['headline'] ?? null,
            'summary' => $profile['summary'] ?? null,
            'awards' => is_array($profile['awards'] ?? null)
                ? collect($profile['awards'])->filter()->implode("\n")
                : ($profile['awards'] ?? null),
            'linkedin_url' => $profile['linkedin_url'] ?? null,
            'portfolio_url' => $profile['portfolio_url'] ?? null,
        ])->filter(fn ($value) => filled($value))->all();
    }

    private function awardsTextFromImport(mixed $awards): ?string
    {
        $text = is_array($awards)
            ? collect($awards)->filter()->implode("\n")
            : $awards;

        return filled($text) ? (string) $text : null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, string>  $keys
     */
    private function importValue(array $item, array $keys, string $fallback): string
    {
        foreach ($keys as $key) {
            if (filled($item[$key] ?? null)) {
                return (string) $item[$key];
            }
        }

        return $fallback;
    }

    /**
     * @param  array<int, array<string, mixed>>  $experiences
     */
    private function replaceExperiences(CvProfile $cvProfile, array $experiences): void
    {
        $cvProfile->experiences()->delete();

        foreach (array_values($experiences) as $index => $experience) {
            if (! is_array($experience)) {
                continue;
            }

            $dates = $this->periodDates($experience['period'] ?? null, true);

            $cvProfile->experiences()->create([
                'position' => $this->importValue($experience, ['position', 'title'], 'Puesto por revisar'),
                'company' => $this->importValue($experience, ['company', 'organization'], 'Empresa por revisar'),
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
                'is_current' => $dates['is_current'],
                'description' => $experience['description'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $educationItems
     */
    private function replaceEducation(CvProfile $cvProfile, array $educationItems): void
    {
        $cvProfile->education()->delete();

        foreach (array_values($educationItems) as $index => $education) {
            if (! is_array($education)) {
                continue;
            }

            $dates = $this->periodDates($education['period'] ?? null, false);

            $cvProfile->education()->create([
                'degree' => $this->importValue($education, ['degree', 'title'], 'Estudio por revisar'),
                'institution' => $this->importValue($education, ['institution', 'organization'], 'Institucion por revisar'),
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
                'description' => $education['description'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $items
     */
    private function replaceSkills(CvProfile $cvProfile, string $type, array $items): void
    {
        $cvProfile->skills()->where('type', $type)->delete();

        foreach (array_values($items) as $index => $item) {
            if (! filled($item)) {
                continue;
            }

            $cvProfile->skills()->create([
                'name' => trim((string) $item),
                'type' => $type,
                'category' => $type === 'language' ? 'Idioma' : null,
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseExperienceBlocks(?string $text): array
    {
        return $this->parseBlocks($text, ['position', 'company']);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseEducationBlocks(?string $text): array
    {
        return $this->parseBlocks($text, ['degree', 'institution']);
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
}
