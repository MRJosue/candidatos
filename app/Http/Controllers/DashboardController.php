<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return view('dashboard', [
            'talentCount' => $user->talents()->count(),
            'activeTalentCount' => $user->talents()->where('status', 'active')->count(),
            'activeVacancyCount' => $user->vacancies()->where('status', 'open')->count(),
            'applicationCount' => $user->jobApplications()->count(),
            'cvCount' => $user->cvProfiles()->count(),
            'purchaseCount' => $user->purchases()->where('status', 'paid')->count(),
            'companyCount' => $user->companies()->count(),
            'positionCount' => $user->positions()->count(),
            'recentTalents' => $user->talents()
                ->latest()
                ->limit(5)
                ->get(),
            'openVacancies' => $user->vacancies()
                ->with(['company', 'position'])
                ->withCount('applications')
                ->where('status', 'open')
                ->latest()
                ->limit(5)
                ->get(),
            'pipelineStatuses' => $user->jobApplications()
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->orderByDesc('total')
                ->get(),
            'nextAppointments' => $user->appointments()
                ->with('service')
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
