<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvSkillRequest;
use App\Http\Requests\UpdateCvSkillRequest;
use App\Models\CvSkill;
use App\Models\CvProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CvSkillController extends Controller
{
    private const REORDER_COLUMNS = [
        'software' => 'software',
        'skills' => 'skill',
        'languages' => 'language',
        'soft_skills' => 'soft_skill',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        return view('cv.skills.index', [
            'profile' => $cvProfile,
            'skills' => $cvProfile->skills()->paginate(30),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        return view('cv.skills.create', compact('cvProfile'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCvSkillRequest $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $cvProfile->skills()->create($request->validated());

        return redirect()->route('cv.show', $cvProfile)->with('status', 'Seccion agregada.');
    }

    public function reorder(Request $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validate([
            'columns' => ['required', 'array'],
            'columns.software' => ['present', 'array'],
            'columns.skills' => ['present', 'array'],
            'columns.languages' => ['present', 'array'],
            'columns.soft_skills' => ['present', 'array'],
            'columns.*.*' => [
                'integer',
                Rule::exists('cv_skills', 'id')->where('cv_profile_id', $cvProfile->id),
            ],
        ]);

        $orderedIds = collect($data['columns'])
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($orderedIds) !== count(array_unique($orderedIds))) {
            return response()->json(['message' => 'El orden contiene elementos duplicados.'], 422);
        }

        DB::transaction(function () use ($data, $cvProfile): void {
            foreach (self::REORDER_COLUMNS as $column => $type) {
                foreach (array_values($data['columns'][$column] ?? []) as $index => $skillId) {
                    $cvProfile->skills()
                        ->whereKey($skillId)
                        ->update([
                            'type' => $type,
                            'sort_order' => $index + 1,
                        ]);
                }
            }
        });

        return response()->json([
            'message' => 'Orden de habilidades actualizado.',
            'columns' => $data['columns'],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CvSkill $cvSkill)
    {
        return redirect()->route('cv.show', $cvSkill->cvProfile);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CvSkill $cvSkill)
    {
        $this->authorize('update', $cvSkill->cvProfile);

        return view('cv.skills.edit', compact('cvSkill'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCvSkillRequest $request, CvSkill $cvSkill)
    {
        $this->authorize('update', $cvSkill->cvProfile);

        $cvSkill->update($request->validated());

        return redirect()->route('cv.show', $cvSkill->cvProfile)->with('status', 'Seccion actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CvSkill $cvSkill)
    {
        $this->authorize('update', $cvSkill->cvProfile);

        $profile = $cvSkill->cvProfile;
        $cvSkill->delete();

        return redirect()->route('cv.show', $profile)->with('status', 'Seccion eliminada.');
    }
}
