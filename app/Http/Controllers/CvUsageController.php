<?php

namespace App\Http\Controllers;

use App\Services\CvUsageService;
use Illuminate\Http\Request;

class CvUsageController extends Controller
{
    public function __invoke(Request $request, CvUsageService $usageService)
    {
        $summary = $usageService->summary($request->user());
        $subscription = $summary['subscription'];

        return view('usage.index', [
            'summary' => $summary,
            'events' => $subscription->events()
                ->with(['cvProfile:id,title,full_name', 'user:id,name'])
                ->where('occurred_at', '>=', $subscription->current_period_starts_at)
                ->where('occurred_at', '<', $subscription->current_period_ends_at)
                ->latest('occurred_at')
                ->paginate(20),
            'usageService' => $usageService,
        ]);
    }
}
