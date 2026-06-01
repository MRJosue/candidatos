<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvExperienceRequest;
use App\Http\Requests\UpdateCvExperienceRequest;
use App\Models\CvExperience;
use App\Models\CvProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function reverseOrder(CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $experiences = $cvProfile->experiences()->get();

        DB::transaction(function () use ($experiences): void {
            $experiences
                ->reverse()
                ->values()
                ->each(fn (CvExperience $item, int $index) => $item->update(['sort_order' => $index + 1]));
        });

        return redirect()->route('cv.show', $cvProfile)->with('status', 'Orden de experiencia invertido.');
    }

    public function move(Request $request, CvExperience $cvExperience)
    {
        $profile = $cvExperience->cvProfile;

        $this->authorize('update', $profile);

        $direction = $request->input('direction');

        if (! in_array($direction, ['up', 'down'], true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Direccion de movimiento invalida.'], 422);
            }

            return redirect()->route('cv.show', $profile)->withErrors(['direction' => 'Direccion de movimiento invalida.']);
        }

        $orderedIds = [];

        DB::transaction(function () use ($profile, $cvExperience, $direction, &$orderedIds): void {
            $items = $profile->experiences()->get()->values()->all();
            $currentIndex = collect($items)->search(fn (CvExperience $item) => $item->is($cvExperience));

            if ($currentIndex === false) {
                return;
            }

            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

            if (! isset($items[$targetIndex])) {
                $orderedIds = collect($items)->pluck('id')->all();

                return;
            }

            $movedItem = array_splice($items, $currentIndex, 1)[0];
            array_splice($items, $targetIndex, 0, [$movedItem]);

            foreach ($items as $index => $item) {
                $item->update(['sort_order' => $index + 1]);
            }

            $orderedIds = collect($items)->pluck('id')->all();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Orden de experiencia actualizado.',
                'ordered_ids' => $orderedIds,
                'redirect_url' => route('cv.show', $profile),
            ]);
        }

        return redirect()->route('cv.show', $profile)->with('status', 'Orden de experiencia actualizado.');
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
