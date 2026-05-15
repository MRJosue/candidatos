<?php

namespace App\Http\Controllers;

use App\Models\JobApplication;
use Illuminate\Http\Request;

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
            'pipelineStages' => $user->jobApplications()
                ->selectRaw($this->normalizedStageSql().' as stage, count(*) as total')
                ->groupByRaw($this->normalizedStageSql())
                ->orderByDesc('total')
                ->get(),
            'nextAppointments' => $user->appointments()
                ->with(['talent', 'vacancy.company', 'vacancy.position'])
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')
                ->limit(3)
                ->get(),
        ]);
    }

    private function normalizedStageSql(): string
    {
        $cases = collect(JobApplication::LEGACY_STAGE_MAP)
            ->map(fn (string $normalized, string $legacy) => "when '{$legacy}' then '{$normalized}'")
            ->implode(' ');

        return "case stage {$cases} else stage end";
    }
}
