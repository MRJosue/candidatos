<?php

namespace App\Http\Controllers;

use App\Models\CvProfile;
use App\Models\CvTemplate;
use Illuminate\Http\Request;

class CvTemplateController extends Controller
{
    public function index()
    {
        return view('templates.index', [
            'templates' => CvTemplate::where('is_active', true)->orderBy('is_premium')->orderBy('name')->get(),
            'purchasedTemplateIds' => auth()->user()?->purchases()->where('status', 'paid')->pluck('cv_template_id')->all() ?? [],
        ]);
    }

    public function show(CvTemplate $template)
    {
        return view('templates.show', compact('template'));
    }

    public function select(Request $request, CvTemplate $template, CvProfile $cvProfile)
    {
        $this->authorize('update', $cvProfile);

        $hasPurchase = $request->user()->purchases()
            ->where('cv_template_id', $template->id)
            ->where('status', 'paid')
            ->exists();

        abort_if($template->is_premium && ! $hasPurchase, 403, 'Compra esta plantilla para asignarla a tu CV.');

        $cvProfile->update(['cv_template_id' => $template->id]);

        return redirect()->route('cv.show', $cvProfile)->with('status', 'Plantilla seleccionada.');
    }
}
