<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvSkillRequest;
use App\Http\Requests\UpdateCvSkillRequest;
use App\Models\CvSkill;
use App\Models\CvProfile;

class CvSkillController extends Controller
{
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
