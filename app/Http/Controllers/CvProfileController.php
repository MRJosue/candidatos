<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvProfileRequest;
use App\Http\Requests\UpdateCvProfileRequest;
use App\Models\CvProfile;
use App\Models\CvUsageEvent;
use App\Models\CvTemplate;
use App\Models\Talent;
use App\Services\CvAiDocumentImportService;
use App\Services\CvDocumentImportService;
use App\Services\CvTranslationService;
use App\Services\CvUsageService;
use App\Services\CvWordDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CvProfileController extends Controller
{
    private const SECTION_TEXT_LIMITS = [
        'experiences_text' => 60000,
        'education_text' => 30000,
        'software_text' => 20000,
        'skills_text' => 20000,
        'languages_text' => 12000,
        'certifications_text' => 20000,
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $visibleUserIds = $user->visibleCvUserIds();
        $filters = $this->indexFilters($request);
        $perPage = $this->perPage($request);

        $profiles = CvProfile::query()
            ->with(['template', 'talent', 'user'])
            ->whereIn('user_id', $visibleUserIds)
            ->when($filters['talent'] !== '', function ($query) use ($filters): void {
                $query->whereHas('talent', function ($query) use ($filters): void {
                    collect(preg_split('/\s+/', $filters['talent']) ?: [])
                        ->filter()
                        ->each(function (string $term) use ($query): void {
                            $query->where(function ($query) use ($term): void {
                                $query->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('last_name', 'like', "%{$term}%")
                                    ->orWhere('email', 'like', "%{$term}%");
                            });
                        });
                });
            })
            ->when($filters['cv'] !== '', function ($query) use ($filters): void {
                collect(preg_split('/\s+/', $filters['cv']) ?: [])
                    ->filter()
                    ->each(function (string $term) use ($query): void {
                        $query->where(function ($query) use ($term): void {
                            $query->where('title', 'like', "%{$term}%")
                                ->orWhere('full_name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        });
                    });
            })
            ->when($filters['language'] !== '', fn ($query) => $query->where('language', $filters['language']))
            ->when($filters['template'] !== '', fn ($query) => $query->where('cv_template_id', $filters['template']))
            ->when($filters['updated_date'] !== '', fn ($query) => $query->whereDate('updated_at', $filters['updated_date']))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        return view('cv.index', [
            'profiles' => $profiles,
            'profilesByTalent' => $profiles
                ->getCollection()
                ->groupBy(fn (CvProfile $profile) => $profile->talent_id ?: 'unassigned'),
            'talents' => $user->talents()->with('cvProfiles:id,talent_id,title,language,is_primary')->orderBy('last_name')->orderBy('first_name')->get(),
            'filters' => $filters,
            'filterOptions' => [
                'languages' => CvProfile::languageOptions(),
                'templates' => CvTemplate::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'),
            ],
            'perPage' => $perPage,
            'perPageOptions' => $this->perPageOptions(),
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
            'languageOptions' => CvProfile::languageOptions(),
            'documentImport' => $documentImport,
            'sectionText' => $this->sectionTextFromImport($documentImport),
        ]);
    }

    public function createForTalent(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        if (! $this->talentCanReceiveCv($talent)) {
            return redirect()
                ->route('talents.show', $talent)
                ->withErrors(['talent_id' => 'Este talento ya tiene el maximo de 2 CVs.']);
        }

        $documentImport = session($this->createDocumentImportSessionKey());

        return view('cv.create', [
            'profile' => $this->profileWithImportDefaults(new CvProfile([
                'talent_id' => $talent->id,
                'title' => 'CV '.$talent->full_name,
                'full_name' => $talent->full_name,
                'language' => 'es',
                'cv_template_id' => CvTemplate::defaultTemplate()?->id,
            ]), $documentImport),
            'talent' => $talent,
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
            'languageOptions' => CvProfile::languageOptions(),
            'documentImport' => $documentImport,
            'sectionText' => $this->sectionTextFromImport($documentImport),
        ]);
    }

    public function storeForTalent(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        $data = $request->validate([
            'language' => ['nullable', Rule::in(array_keys(CvProfile::languageOptions()))],
        ]);
        $language = $data['language'] ?? 'es';
        $existingProfile = $talent->cvProfiles()
            ->where('language', $language)
            ->first();

        if ($existingProfile) {
            return redirect()
                ->route('cv.show', $existingProfile)
                ->with('status', 'Este talento ya tiene un CV en ese idioma.');
        }

        $spanishProfile = $talent->cvProfiles()
            ->where('language', 'es')
            ->first();

        if ($language === 'en' && ! $spanishProfile) {
            return back()->withErrors(['language' => 'Primero crea el CV en espanol antes de crear el CV en ingles.']);
        }

        if (! $this->talentCanReceiveCv($talent)) {
            return back()->withErrors(['talent_id' => 'Este talento ya tiene el maximo de 2 CVs.']);
        }

        $profile = $request->user()->cvProfiles()->create($this->profileDataFromTalent($talent, $language, $spanishProfile));

        return redirect()
            ->route('cv.show', $profile)
            ->with('status', 'CV creado desde el talento. Puedes revisarlo en la vista de cards.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCvProfileRequest $request)
    {
        $talent = $this->validatedTalent($request);
        $data = $request->validated();

        $this->ensureTalentCanReceiveCv($talent);

        $sectionData = $this->validatedSectionText($request);

        $profile = DB::transaction(function () use ($request, $talent, $data, $sectionData): CvProfile {
            $profile = $request->user()->cvProfiles()->create([
                ...$this->profileDataForStorage($data),
                'talent_id' => $talent?->id,
                'cv_template_id' => $data['cv_template_id'] ?? CvTemplate::defaultTemplate()?->id,
                'is_primary' => $talent ? ! $talent->cvProfiles()->exists() : false,
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
            'profile' => $cvProfile->load(['template', 'experiences', 'education', 'skills', 'translations', 'sourceCvProfile']),
            'templates' => CvTemplate::where('is_active', true)->orderBy('is_premium')->orderBy('name')->get(),
            'purchasedTemplateIds' => auth()->user()?->purchases()->where('status', 'paid')->pluck('cv_template_id')->all() ?? [],
            'languageOptions' => CvProfile::languageOptions(),
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
            'languageOptions' => CvProfile::languageOptions(),
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

        $this->ensureTalentCanReceiveCv($talent, $cvProfile);

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
            $this->ensureTalentCanReceiveCv($talent, $cvProfile);
        }

        DB::transaction(function () use ($cvProfile, $talent): void {
            $cvProfile->update([
                'talent_id' => $talent?->id,
                'is_primary' => $talent ? ! $talent->cvProfiles()->whereKeyNot($cvProfile->id)->exists() : false,
            ]);
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

    public function translate(Request $request, CvProfile $cvProfile, CvTranslationService $translationService, CvUsageService $usageService)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validate([
            'target_language' => ['required', Rule::in(array_keys(CvProfile::languageOptions()))],
        ]);

        if (($cvProfile->language ?: 'es') === $data['target_language']) {
            return back()->withErrors(['target_language' => 'El CV ya esta en ese idioma.']);
        }

        if ($cvProfile->sourceCvProfile && ($cvProfile->sourceCvProfile->language ?: 'es') === $data['target_language']) {
            return redirect()
                ->route('cv.edit', $cvProfile->sourceCvProfile)
                ->with('status', 'Ya existia una version en ese idioma. Puedes revisarla y ajustarla.');
        }

        $existingTranslation = $cvProfile->translations()
            ->where('language', $data['target_language'])
            ->first();

        if ($existingTranslation) {
            return redirect()
                ->route('cv.edit', $existingTranslation)
                ->with('status', 'Ya existia una version en ese idioma. Puedes revisarla y ajustarla.');
        }

        $this->ensureTalentCanReceiveCv($cvProfile->talent, $cvProfile);

        try {
            $usageService->ensureCanConsume($request->user());
            $translated = $translationService->translate($cvProfile, $data['target_language']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['target_language' => $exception->getMessage()]);
        }

        try {
            $newProfile = DB::transaction(fn () => $this->createTranslatedProfile($cvProfile, $translated, $data['target_language']));
        } catch (QueryException $exception) {
            $existingTranslation = $cvProfile->translations()
                ->where('language', $data['target_language'])
                ->first();

            if (! $existingTranslation || ! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            return redirect()
                ->route('cv.edit', $existingTranslation)
                ->with('status', 'Ya existia una version en ese idioma. Puedes revisarla y ajustarla.');
        }

        $usageService->record($request->user(), CvUsageEvent::TYPE_TRANSLATION_AI, $cvProfile, [
            'target_language' => $data['target_language'],
            'translated_cv_profile_id' => $newProfile->id,
        ]);

        return redirect()
            ->route('cv.edit', $newProfile)
            ->with('status', 'Version traducida creada. Revisala antes de descargar el PDF.');
    }

    public function updateSectionOrder(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validate([
            'side' => ['required', 'array'],
            'side.*' => ['required', 'string', 'in:software,skills,languages,certifications'],
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
        CvUsageService $usageService,
    ) {
        $this->authorize('update', $cvProfile);

        $import = $this->analyzeDocumentImport($request, $importService, $aiImportService, $usageService, $cvProfile);

        if ($import instanceof RedirectResponse) {
            return $import;
        }

        session()->put($this->documentImportSessionKey($cvProfile), $import);

        return redirect()
            ->route('cv.edit', $cvProfile)
            ->with('status', 'Análisis listo. Revisa la previsualización antes de aplicar cambios.');
    }

    public function importDocumentForCreateWithAi(
        Request $request,
        CvDocumentImportService $importService,
        CvAiDocumentImportService $aiImportService,
        CvUsageService $usageService,
    ) {
        $import = $this->analyzeDocumentImport($request, $importService, $aiImportService, $usageService);

        if ($import instanceof RedirectResponse) {
            return $import;
        }

        session()->put($this->createDocumentImportSessionKey(), $import);

        $talent = filled($request->input('talent_id'))
            ? $request->user()->talents()->find($request->integer('talent_id'))
            : null;

        return redirect()
            ->route($talent ? 'talents.cv.create' : 'cv.create', $talent ?: [])
            ->with('status', 'Análisis listo. Revisa la previsualización antes de guardar el CV.');
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
    public function destroy(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('delete', $cvProfile);

        $talent = $cvProfile->talent;
        $cvProfile->delete();

        if ($request->input('redirect_to') === 'talent' && $talent) {
            return redirect()->route('talents.show', $talent)->with('status', 'CV eliminado.');
        }

        return redirect()->route('cv.index')->with('status', 'CV eliminado.');
    }

    public function preview(CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        return view('cv.preview', [
            'profile' => $cvProfile->load(['template', 'experiences', 'education', 'skills']),
        ]);
    }

    public function download(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        $data = $request->validate([
            'language' => ['nullable', Rule::in(array_keys(CvProfile::languageOptions()))],
        ]);

        $profile = $this->profileForDownloadLanguage($cvProfile, $data['language'] ?? null);

        if (! $profile) {
            return back()->withErrors(['language' => 'No hay un CV disponible en el idioma seleccionado.']);
        }

        $profile->load(['template', 'experiences', 'education', 'skills']);

        $paper = $profile->template?->slug === 'academico-bullet' ? 'letter' : 'a4';

        return Pdf::loadView('cv.pdf', compact('profile'))
            ->setPaper($paper)
            ->download(str($profile->title)->slug().'.pdf');
    }

    public function downloadWord(Request $request, CvProfile $cvProfile, CvWordDocumentService $wordDocumentService)
    {
        $this->authorize('view', $cvProfile);

        $data = $request->validate([
            'language' => ['nullable', Rule::in(array_keys(CvProfile::languageOptions()))],
        ]);

        $profile = $this->profileForDownloadLanguage($cvProfile, $data['language'] ?? null);

        if (! $profile) {
            return back()->withErrors(['language' => 'No hay un CV disponible en el idioma seleccionado.']);
        }

        $profile->load(['template', 'experiences', 'education', 'skills']);

        return response($wordDocumentService->output($profile), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.(str($profile->title ?: $profile->full_name)->slug()->value() ?: 'cv-'.$profile->id).'.docx"',
        ]);
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

        return $talent;
    }

    private function talentCanReceiveCv(?Talent $talent, ?CvProfile $currentProfile = null): bool
    {
        if (! $talent) {
            return true;
        }

        return $talent->cvProfiles()
            ->when($currentProfile, fn ($query) => $query->whereKeyNot($currentProfile->id))
            ->count() < CvProfile::MAX_PER_TALENT;
    }

    private function ensureTalentCanReceiveCv(?Talent $talent, ?CvProfile $currentProfile = null): void
    {
        if ($this->talentCanReceiveCv($talent, $currentProfile)) {
            return;
        }

        throw ValidationException::withMessages([
            'talent_id' => 'Este talento ya tiene el maximo de 2 CVs.',
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function validatedSectionText(Request $request): array
    {
        return Validator::make(
            $request->all(),
            [
                'experiences_text' => ['nullable', 'string', 'max:'.self::SECTION_TEXT_LIMITS['experiences_text']],
                'education_text' => ['nullable', 'string', 'max:'.self::SECTION_TEXT_LIMITS['education_text']],
                'software_text' => ['nullable', 'string', 'max:'.self::SECTION_TEXT_LIMITS['software_text']],
                'skills_text' => ['nullable', 'string', 'max:'.self::SECTION_TEXT_LIMITS['skills_text']],
                'languages_text' => ['nullable', 'string', 'max:'.self::SECTION_TEXT_LIMITS['languages_text']],
                'certifications_text' => ['nullable', 'string', 'max:'.self::SECTION_TEXT_LIMITS['certifications_text']],
            ],
            [
                'max.string' => 'El campo :attribute es demasiado largo. Puedes pegar bastante contenido, pero este bloque excede el límite permitido.',
            ],
            [
                'experiences_text' => 'experiencia',
                'education_text' => 'educación',
                'software_text' => 'software',
                'skills_text' => 'habilidades',
                'languages_text' => 'idiomas',
                'certifications_text' => 'certificaciones',
            ],
        )->validate();
    }

    private function hasSectionTextInput(Request $request): bool
    {
        return $request->hasAny([
            'experiences_text',
            'education_text',
            'software_text',
            'skills_text',
            'languages_text',
            'certifications_text',
        ]);
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function replaceSectionsFromText(CvProfile $cvProfile, array $data): void
    {
        $this->replaceExperiences($cvProfile, $this->parseExperienceBlocks($data['experiences_text'] ?? ''));
        $this->replaceEducation($cvProfile, $this->parseEducationBlocks($data['education_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'software', $this->parseList($data['software_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'skill', $this->parseList($data['skills_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'language', $this->parseList($data['languages_text'] ?? ''));
        $this->replaceSkills($cvProfile, 'certification', $this->parseList($data['certifications_text'] ?? ''));
        $cvProfile->update(['awards' => null]);
    }

    private function profileDataFromTalent(Talent $talent, string $language = 'es', ?CvProfile $sourceProfile = null): array
    {
        $languageSuffix = $language === 'en' ? ' - Ingles' : '';

        return $this->profileDataForStorage([
            'talent_id' => $talent->id,
            'title' => 'CV '.$talent->full_name.$languageSuffix,
            'full_name' => $talent->full_name,
            'language' => $language,
            'source_cv_profile_id' => $language === 'en' ? $sourceProfile?->id : null,
            'section_order' => CvProfile::defaultSectionOrder(),
            'cv_template_id' => CvTemplate::defaultTemplate()?->id,
            'is_primary' => ! $talent->cvProfiles()->exists(),
        ]);
    }

    private function profileDataForStorage(array $data): array
    {
        $data['email'] = $data['email'] ?? '';
        $data['language'] = $data['language'] ?? 'es';

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
        CvUsageService $usageService,
        ?CvProfile $cvProfile = null,
    ): array|RedirectResponse {
        $data = Validator::make(
            $request->all(),
            [
                'cv_document' => ['required', 'file', 'mimes:pdf,docx,txt', 'max:6144'],
            ],
            [
                'cv_document.required' => 'Selecciona un archivo para analizar.',
                'cv_document.file' => 'El documento del CV no se pudo leer correctamente.',
                'cv_document.mimes' => 'Este servidor no admite archivos .doc. Si tu CV está en Word antiguo, abre el documento, guarda o copia todo el contenido a un archivo .txt, y súbelo como TXT, DOCX o PDF con texto real.',
                'cv_document.max' => 'El archivo del CV supera el límite de 6 MB. Reduce el tamaño o guárdalo como TXT o DOCX.',
            ],
            [
                'cv_document' => 'documento del CV',
            ],
        )->validate();

        try {
            $usageService->ensureCanConsume($request->user());
            $text = $importService->extractText($data['cv_document']);
            $analysis = $aiImportService->analyze($text);

            $usageService->record($request->user(), CvUsageEvent::TYPE_IMPORT_AI, $cvProfile, [
                'original_name' => $data['cv_document']->getClientOriginalName(),
                'file_mime' => $data['cv_document']->getClientMimeType(),
                'file_size' => $data['cv_document']->getSize(),
            ]);

            return [
                'original_name' => $data['cv_document']->getClientOriginalName(),
                'source' => 'ai',
                'parsed' => [
                    ...$analysis,
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
                ->withErrors(['cv_document_ai' => 'No se pudo analizar el documento. Intenta de nuevo con un PDF con texto real, DOCX o TXT.'])
                ->withInput();
        }
    }

    private function validatedImportApplyOptions(Request $request): array
    {
        return $request->validate([
            'apply_profile' => ['nullable', 'boolean'],
            'apply_experiences' => ['nullable', 'boolean'],
            'apply_education' => ['nullable', 'boolean'],
            'apply_software' => ['nullable', 'boolean'],
            'apply_skills' => ['nullable', 'boolean'],
            'apply_languages' => ['nullable', 'boolean'],
            'apply_certifications' => ['nullable', 'boolean'],
        ]);
    }

    private function applyImportedData(CvProfile $cvProfile, array $parsed, array $data): void
    {
        if ($data['apply_profile'] ?? false) {
            $profileData = $this->profileImportData($parsed['profile'] ?? []);

            $cvProfile->update($profileData);
        }

        if ($data['apply_experiences'] ?? false) {
            $this->replaceExperiences($cvProfile, $parsed['experiences'] ?? []);
        }

        if ($data['apply_education'] ?? false) {
            $this->replaceEducation($cvProfile, $parsed['education'] ?? []);
        }

        if ($data['apply_software'] ?? false) {
            $this->replaceSkills($cvProfile, 'software', $parsed['software'] ?? []);
        }

        if ($data['apply_skills'] ?? false) {
            $this->replaceSkills($cvProfile, 'skill', $parsed['skills'] ?? []);
        }

        if ($data['apply_languages'] ?? false) {
            $this->replaceSkills($cvProfile, 'language', $parsed['languages'] ?? []);
        }

        if ($data['apply_certifications'] ?? false) {
            $this->replaceSkills($cvProfile, 'certification', $parsed['awards'] ?? []);
            $cvProfile->update(['awards' => null]);
        }

    }

    private function createTranslatedProfile(CvProfile $source, array $translated, string $targetLanguage): CvProfile
    {
        $source->loadMissing(['experiences', 'education', 'skills']);

        $profileData = collect($translated['profile'] ?? [])
            ->only([
                'title',
                'full_name',
                'email',
                'phone',
                'location',
                'headline',
                'tagline',
                'summary',
                'objective',
                'skills_section_title',
                'soft_skills_section_title',
                'awards',
                'leadership_activities',
                'interests',
                'linkedin_url',
                'portfolio_url',
            ])
            ->map(fn ($value) => $this->cleanTranslatedText($value))
            ->filter(fn ($value) => filled($value))
            ->all();

        $newProfile = $source->user->cvProfiles()->create([
            ...$source->only([
                'talent_id',
                'cv_template_id',
                'title',
                'full_name',
                'email',
                'phone',
                'location',
                'headline',
                'tagline',
                'summary',
                'objective',
                'skills_section_title',
                'soft_skills_section_title',
                'section_order',
                'awards',
                'leadership_activities',
                'interests',
                'linkedin_url',
                'portfolio_url',
            ]),
            ...$profileData,
            'title' => $profileData['title'] ?? $this->translatedTitle($source, $targetLanguage),
            'language' => $targetLanguage,
            'source_cv_profile_id' => $source->id,
            'is_primary' => false,
        ]);

        foreach ($source->experiences->values() as $index => $experience) {
            $translatedExperience = $translated['experiences'][$index] ?? [];

            $newProfile->experiences()->create([
                ...$experience->only([
                    'company',
                    'position',
                    'location',
                    'start_date',
                    'end_date',
                    'is_current',
                    'description',
                    'tools_used',
                    'sort_order',
                ]),
                ...collect($translatedExperience)
                    ->only(['company', 'position', 'location', 'description', 'tools_used'])
                    ->map(fn ($value) => $this->cleanTranslatedText($value))
                    ->filter(fn ($value) => filled($value))
                    ->all(),
            ]);
        }

        foreach ($source->education->values() as $index => $education) {
            $translatedEducation = $translated['education'][$index] ?? [];

            $newProfile->education()->create([
                ...$education->only([
                    'institution',
                    'location',
                    'degree',
                    'field',
                    'gpa',
                    'honors',
                    'thesis',
                    'relevant_coursework',
                    'start_date',
                    'end_date',
                    'description',
                    'sort_order',
                ]),
                ...collect($translatedEducation)
                    ->only(['institution', 'location', 'degree', 'field', 'gpa', 'honors', 'thesis', 'relevant_coursework', 'description'])
                    ->map(fn ($value) => $this->cleanTranslatedText($value))
                    ->filter(fn ($value) => filled($value))
                    ->all(),
            ]);
        }

        foreach ($source->skills->values() as $index => $skill) {
            $translatedSkill = $translated['skills'][$index] ?? [];

            $newProfile->skills()->create([
                ...$skill->only(['name', 'category', 'type', 'level', 'sort_order']),
                ...collect($translatedSkill)
                    ->only(['name', 'category', 'type'])
                    ->map(fn ($value) => $this->cleanTranslatedText($value))
                    ->filter(fn ($value) => filled($value))
                    ->all(),
            ]);
        }

        return $newProfile;
    }

    private function cleanTranslatedText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return preg_match('/^(?:null|n\/a|na|none|-|--)$/iu', $value) ? null : $value;
    }

    private function profileForDownloadLanguage(CvProfile $profile, ?string $language): ?CvProfile
    {
        if (! $language || ($profile->language ?: 'es') === $language) {
            return $profile;
        }

        $rootId = $profile->source_cv_profile_id ?: $profile->id;

        $variant = CvProfile::query()
            ->where('user_id', $profile->user_id)
            ->where('language', $language)
            ->where(function ($query) use ($rootId): void {
                $query->whereKey($rootId)
                    ->orWhere('source_cv_profile_id', $rootId);
            })
            ->first();

        if ($variant || ! $profile->talent_id) {
            return $variant;
        }

        return CvProfile::query()
            ->where('user_id', $profile->user_id)
            ->where('talent_id', $profile->talent_id)
            ->where('language', $language)
            ->orderByDesc('is_primary')
            ->latest()
            ->first();
    }

    private function translatedTitle(CvProfile $source, string $targetLanguage): string
    {
        $languageLabel = CvProfile::languageOptions()[$targetLanguage] ?? strtoupper($targetLanguage);

        return trim(($source->title ?: 'CV').' - '.$languageLabel);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return $sqlState === '23000' || in_array($driverCode, ['1062', '19'], true);
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
                    filled($experience->tools_used) ? 'Herramientas Utilizadas: '.$experience->tools_used : null,
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
            'software' => $cvProfile->skills->where('type', 'software')->pluck('name')->implode('; '),
            'skills' => $cvProfile->skills->where('type', 'skill')->pluck('name')->implode('; '),
            'languages' => $cvProfile->skills->where('type', 'language')->pluck('name')->implode('; '),
            'certifications' => $cvProfile->skills->where('type', 'certification')->pluck('name')->implode('; ')
                ?: $this->awardsTextFromImport($cvProfile->awards),
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
                    filled($experience['tools_used'] ?? null) ? 'Herramientas Utilizadas: '.$experience['tools_used'] : null,
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
            'software' => collect($parsed['software'] ?? [])->filter()->implode('; '),
            'skills' => collect($parsed['skills'] ?? [])->filter()->implode('; '),
            'languages' => collect($parsed['languages'] ?? [])->filter()->implode('; '),
            'certifications' => collect($parsed['awards'] ?? [])->filter()->implode('; '),
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
                'tools_used' => $experience['tools_used'] ?? null,
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
        return $this->parseBlocks($this->splitEmbeddedExperienceHeaders($text), ['position', 'company']);
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

                $toolsUsed = null;
                $descriptionLines = $lines->reject(function ($line) use (&$toolsUsed) {
                    if (preg_match('/^herramientas(?:\s+utilizadas)?\s*:\s*(.+)$/iu', $line, $matches)) {
                        $toolsUsed = trim($matches[1]);

                        return true;
                    }

                    return false;
                });

                return [
                    $keys[0] => $parts[0] ?: null,
                    $keys[1] => $parts[1] ?: null,
                    'period' => $parts[2] ?: null,
                    'description' => $descriptionLines->implode("\n") ?: null,
                    'tools_used' => $toolsUsed,
                ];
            })
            ->filter(fn ($item) => filled($item[$keys[0]]) || filled($item[$keys[1]]))
            ->values()
            ->all();
    }

    private function splitEmbeddedExperienceHeaders(?string $text): string
    {
        $lines = collect(preg_split('/\R/u', (string) $text) ?: []);
        $normalized = [];
        $hasContentInCurrentBlock = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $normalized[] = '';
                $hasContentInCurrentBlock = false;

                continue;
            }

            if ($hasContentInCurrentBlock && $this->looksLikeExperienceHeader($trimmed)) {
                $normalized[] = '';
            }

            $normalized[] = $line;
            $hasContentInCurrentBlock = true;
        }

        return implode("\n", $normalized);
    }

    private function looksLikeExperienceHeader(string $line): bool
    {
        $parts = array_map('trim', explode('|', $line));

        return count($parts) >= 3
            && filled($parts[0] ?? null)
            && filled($parts[1] ?? null)
            && (bool) preg_match('/(?:19|20)\d{2}/', $parts[2] ?? '');
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

    private function indexFilters(Request $request): array
    {
        $validated = $request->validate([
            'talent' => ['nullable', 'string', 'max:160'],
            'cv' => ['nullable', 'string', 'max:160'],
            'language' => ['nullable', Rule::in(array_keys(CvProfile::languageOptions()))],
            'template' => ['nullable', 'integer'],
            'updated_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return [
            'talent' => trim((string) ($validated['talent'] ?? '')),
            'cv' => trim((string) ($validated['cv'] ?? '')),
            'language' => (string) ($validated['language'] ?? ''),
            'template' => isset($validated['template']) ? (string) $validated['template'] : '',
            'updated_date' => (string) ($validated['updated_date'] ?? ''),
        ];
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 10);

        return in_array($perPage, $this->perPageOptions(), true) ? $perPage : 10;
    }

    private function perPageOptions(): array
    {
        return [5, 10, 20, 50, 100];
    }
}
