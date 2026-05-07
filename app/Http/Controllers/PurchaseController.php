<?php

namespace App\Http\Controllers;

use App\Models\CvTemplate;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        return view('purchases.index', [
            'purchases' => $request->user()->purchases()->with('template')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request, CvTemplate $template)
    {
        abort_unless($template->is_premium, 422, 'Esta plantilla no requiere compra.');

        $purchase = $request->user()->purchases()->firstOrCreate(
            ['cv_template_id' => $template->id, 'status' => 'paid'],
            [
                'amount_cents' => $template->price_cents,
                'currency' => $template->currency,
                'paid_at' => now(),
            ]
        );

        return redirect()->route('purchases.index')
            ->with('status', "Compra registrada para {$purchase->template->name}. Conecta STRIPE_KEY, STRIPE_SECRET y stripe_price_id para checkout real con Cashier.");
    }
}
