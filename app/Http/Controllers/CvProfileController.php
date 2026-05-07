<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvProfileRequest;
use App\Http\Requests\UpdateCvProfileRequest;
use App\Models\CvProfile;
use App\Models\CvTemplate;
use App\Models\Talent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        return view('cv.create', [
            'profile' => new CvProfile(),
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function createForTalent(Request $request, Talent $talent)
    {
        abort_unless($talent->recruiter_id === $request->user()->id, 403);

        if ($talent->cvProfile) {
            return redirect()->route('cv.edit', $talent->cvProfile);
        }

        return view('cv.create', [
            'profile' => new CvProfile([
                'talent_id' => $talent->id,
                'title' => 'CV '.$talent->full_name,
                'full_name' => $talent->full_name,
                'email' => $talent->email,
                'phone' => $talent->phone,
                'location' => $talent->location,
                'headline' => $talent->headline ?: $talent->target_position,
                'summary' => $talent->technical_summary,
            ]),
            'talent' => $talent,
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCvProfileRequest $request)
    {
        $talent = $this->validatedTalent($request);

        $profile = $request->user()->cvProfiles()->create([
            ...$request->validated(),
            'talent_id' => $talent?->id,
            'section_order' => CvProfile::defaultSectionOrder(),
        ]);

        return redirect()
            ->route($talent ? 'talents.show' : 'cv.show', $talent ?: $profile)
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
            'profile' => $cvProfile,
            'templates' => CvTemplate::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCvProfileRequest $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $talent = $this->validatedTalent($request);

        $cvProfile->update([
            ...$request->validated(),
            'talent_id' => $talent?->id,
        ]);

        return redirect()
            ->route($talent ? 'talents.show' : 'cv.show', $talent ?: $cvProfile)
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

        DB::transaction(function () use ($cvProfile, $talent): void {
            if ($talent) {
                CvProfile::where('talent_id', $talent->id)
                    ->whereKeyNot($cvProfile->id)
                    ->update(['talent_id' => null]);
            }

            $cvProfile->update(['talent_id' => $talent?->id]);
        });

        return redirect()->route('cv.index')->with('status', 'CV asignado al postulante.');
    }

    public function updateTemplate(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validate([
            'cv_template_id' => ['nullable', 'exists:cv_templates,id'],
        ]);

        $template = filled($data['cv_template_id'] ?? null)
            ? CvTemplate::findOrFail($data['cv_template_id'])
            : null;

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

        return Pdf::loadView('cv.pdf', compact('profile'))
            ->setPaper('letter')
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

        CvProfile::where('talent_id', $talent->id)
            ->when($request->route('cvProfile'), fn ($query, CvProfile $profile) => $query->whereKeyNot($profile->id))
            ->update(['talent_id' => null]);

        return $talent;
    }
}
