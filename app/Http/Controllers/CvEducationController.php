<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCvEducationRequest;
use App\Http\Requests\UpdateCvEducationRequest;
use App\Models\CvEducation;
use App\Models\CvProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function reverseOrder(CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $education = $cvProfile->education()->get();

        DB::transaction(function () use ($education): void {
            $education
                ->reverse()
                ->values()
                ->each(fn (CvEducation $item, int $index) => $item->update(['sort_order' => $index + 1]));
        });

        return redirect()->route('cv.show', $cvProfile)->with('status', 'Orden de educacion invertido.');
    }

    public function move(Request $request, CvEducation $cvEducation)
    {
        $profile = $cvEducation->cvProfile;

        $this->authorize('update', $profile);

        $direction = $request->input('direction');

        if (! in_array($direction, ['up', 'down'], true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Direccion de movimiento invalida.'], 422);
            }

            return redirect()->route('cv.show', $profile)->withErrors(['direction' => 'Direccion de movimiento invalida.']);
        }

        $orderedIds = [];

        DB::transaction(function () use ($profile, $cvEducation, $direction, &$orderedIds): void {
            $items = $profile->education()->get()->values()->all();
            $currentIndex = collect($items)->search(fn (CvEducation $item) => $item->is($cvEducation));

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
                'message' => 'Orden de educacion actualizado.',
                'ordered_ids' => $orderedIds,
                'redirect_url' => route('cv.show', $profile),
            ]);
        }

        return redirect()->route('cv.show', $profile)->with('status', 'Orden de educacion actualizado.');
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
