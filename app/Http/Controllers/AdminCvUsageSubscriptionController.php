<?php

namespace App\Http\Controllers;

use App\Models\CvUsagePlan;
use App\Models\User;
use App\Services\CvUsageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCvUsageSubscriptionController extends Controller
{
    public function index(CvUsageService $usageService)
    {
        $users = User::query()
            ->with(['cvUsageSubscription.plan'])
            ->whereHas('roles', fn ($query) => $query->whereIn('name', User::ACCOUNT_OWNER_ROLES))
            ->orderBy('name')
            ->paginate(15);

        $users->getCollection()->each(fn (User $user) => $usageService->subscriptionFor($user));
        $users->getCollection()->load(['cvUsageSubscription.plan']);

        return view('admin.usage-subscriptions.index', [
            'users' => $users,
        ]);
    }

    public function edit(User $user, CvUsageService $usageService)
    {
        abort_unless($user->isAccountOwner(), 404);

        $subscription = $usageService->subscriptionFor($user);

        return view('admin.usage-subscriptions.edit', [
            'account' => $user,
            'subscription' => $subscription,
            'plans' => CvUsagePlan::query()->where('is_active', true)->orderBy('monthly_quota')->get(),
            'summary' => $usageService->summary($user),
            'accountUsage' => $usageService->accountUsageFor($user, $subscription),
        ]);
    }

    public function update(Request $request, User $user, CvUsageService $usageService)
    {
        abort_unless($user->isAccountOwner(), 404);

        $data = $request->validate([
            'cv_usage_plan_id' => ['required', Rule::exists('cv_usage_plans', 'id')->where('is_active', true)],
            'extra_cv_quota' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'current_period_starts_at' => ['required', 'date'],
            'current_period_ends_at' => ['required', 'date', 'after:current_period_starts_at'],
            'status' => ['required', Rule::in(['active', 'paused', 'cancelled'])],
        ]);

        $data['extra_cv_quota'] = $data['extra_cv_quota'] ?? 0;

        $subscription = $usageService->subscriptionFor($user);
        $subscription->update($data);

        return redirect()
            ->route('admin.usage-subscriptions.edit', $user)
            ->with('status', 'usage-subscription-saved');
    }

    public function resetPeriod(User $user, CvUsageService $usageService)
    {
        abort_unless($user->isAccountOwner(), 404);

        $subscription = $usageService->subscriptionFor($user);
        $usageService->resetCurrentPeriod($subscription);

        return redirect()
            ->route('admin.usage-subscriptions.edit', $user)
            ->with('status', 'usage-subscription-period-reset');
    }
}
