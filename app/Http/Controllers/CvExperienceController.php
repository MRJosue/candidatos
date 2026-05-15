<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvExperienceRequest;
use App\Http\Requests\UpdateCvExperienceRequest;
use App\Models\CvExperience;
use App\Models\CvProfile;

class CvExperienceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        return view('cv.experiences.index', [
            'profile' => $cvProfile,
            'experiences' => $cvProfile->experiences()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        return view('cv.experiences.create', compact('cvProfile'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCvExperienceRequest $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $data = $request->validated();
        $data['is_current'] = $request->boolean('is_current');

        $cvProfile->experiences()->create($data);

        return redirect()->route('cv.show', $cvProfile)->with('status', 'Experiencia agregada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CvExperience $cvExperience)
    {
        return redirect()->route('cv.show', $cvExperience->cvProfile);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CvExperience $cvExperience)
    {
        $this->authorize('update', $cvExperience->cvProfile);

        return view('cv.experiences.edit', compact('cvExperience'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCvExperienceRequest $request, CvExperience $cvExperience)
    {
        $this->authorize('update', $cvExperience->cvProfile);

        $data = $request->validated();
        $data['is_current'] = $request->boolean('is_current');

        $cvExperience->update($data);

        return redirect()->route('cv.show', $cvExperience->cvProfile)->with('status', 'Experiencia actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CvExperience $cvExperience)
    {
        $this->authorize('update', $cvExperience->cvProfile);

        $profile = $cvExperience->cvProfile;
        $cvExperience->delete();

        return redirect()->route('cv.show', $profile)->with('status', 'Experiencia eliminada.');
    }
}
