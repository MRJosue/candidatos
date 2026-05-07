<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvEducationRequest;
use App\Http\Requests\UpdateCvEducationRequest;
use App\Models\CvEducation;
use App\Models\CvProfile;

class CvEducationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CvProfile $cvProfile)
    {
        $this->authorize('view', $cvProfile);

        return view('cv.education.index', [
            'profile' => $cvProfile,
            'education' => $cvProfile->education()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        return view('cv.education.create', compact('cvProfile'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCvEducationRequest $request, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $cvProfile->education()->create($request->validated());

        return redirect()->route('cv.show', $cvProfile)->with('status', 'Educacion agregada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CvEducation $cvEducation)
    {
        return redirect()->route('cv.show', $cvEducation->cvProfile);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CvEducation $cvEducation)
    {
        $this->authorize('update', $cvEducation->cvProfile);

        return view('cv.education.edit', compact('cvEducation'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCvEducationRequest $request, CvEducation $cvEducation)
    {
        $this->authorize('update', $cvEducation->cvProfile);

        $cvEducation->update($request->validated());

        return redirect()->route('cv.show', $cvEducation->cvProfile)->with('status', 'Educacion actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CvEducation $cvEducation)
    {
        $this->authorize('update', $cvEducation->cvProfile);

        $profile = $cvEducation->cvProfile;
        $cvEducation->delete();

        return redirect()->route('cv.show', $profile)->with('status', 'Educacion eliminada.');
    }
}
