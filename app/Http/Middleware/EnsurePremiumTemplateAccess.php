<?php

namespace App\Http\Middleware;

use App\Models\CvTemplate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremiumTemplateAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $template = $request->route('template');

        if (! $template instanceof CvTemplate) {
            $templateId = $request->input('cv_template_id');
            $template = $templateId ? CvTemplate::find($templateId) : null;
        }

        if ($template && $template->is_premium) {
            $hasPurchase = $request->user()?->purchases()
                ->where('cv_template_id', $template->id)
                ->where('status', 'paid')
                ->exists();

            abort_unless($hasPurchase, 403, 'Necesitas comprar esta plantilla premium antes de usarla.');
        }

        return $next($request);
    }
}
