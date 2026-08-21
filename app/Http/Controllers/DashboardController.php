<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\CvProfile;
use App\Models\JobApplication;
use App\Models\Position;
use App\Models\Talent;
use App\Models\Vacancy;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $visibleRecruiterIds = $user->visibleRecruiterUserIds();
        $visibleCvUserIds = $user->visibleCvUserIds();

        return view('dashboard', [
            'talentCount' => Talent::query()->whereIn('recruiter_id', $visibleRecruiterIds)->count(),
            'activeTalentCount' => Talent::query()->whereIn('recruiter_id', $visibleRecruiterIds)->where('status', 'active')->count(),
            'activeVacancyCount' => Vacancy::query()->whereIn('recruiter_id', $visibleRecruiterIds)->where('status', 'open')->count(),
            'applicationCount' => JobApplication::query()->whereIn('recruiter_id', $visibleRecruiterIds)->count(),
            'cvCount' => CvProfile::query()->whereIn('user_id', $visibleCvUserIds)->count(),
            'companyCount' => Company::query()->whereIn('recruiter_id', $visibleRecruiterIds)->count(),
            'positionCount' => Position::query()->whereIn('recruiter_id', $visibleRecruiterIds)->count(),
            'recentTalents' => Talent::query()
                ->whereIn('recruiter_id', $visibleRecruiterIds)
                ->latest()
                ->limit(5)
                ->get(),
            'openVacancies' => Vacancy::query()
                ->whereIn('recruiter_id', $visibleRecruiterIds)
                ->with(['company', 'position'])
                ->withCount('applications')
                ->where('status', 'open')
                ->latest()
                ->limit(5)
                ->get(),
            'pipelineStages' => JobApplication::query()
                ->whereIn('recruiter_id', $visibleRecruiterIds)
                ->selectRaw($this->normalizedStageSql().' as stage, count(*) as total')
                ->groupByRaw($this->normalizedStageSql())
                ->orderByDesc('total')
                ->get(),
            'nextAppointments' => Appointment::query()
                ->whereIn('user_id', $visibleCvUserIds)
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
