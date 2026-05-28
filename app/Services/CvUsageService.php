<?php

namespace App\Services;

use App\Models\CvProfile;
use App\Models\CvUsageEvent;
use App\Models\CvUsagePlan;
use App\Models\CvUsageSubscription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CvUsageService
{
    public function summary(User $user): array
    {
        $subscription = $this->subscriptionFor($user);
        $used = $this->usedInCurrentPeriod($subscription);
        $quota = $subscription->plan->monthly_quota;
        $accountOwner = $this->accountOwnerFor($user);

        return [
            'subscription' => $subscription,
            'plan' => $subscription->plan,
            'accountOwner' => $accountOwner,
            'used' => $used,
            'quota' => $quota,
            'remaining' => max(0, $quota - $used),
            'percentage' => $quota > 0 ? min(100, round(($used / $quota) * 100)) : 0,
        ];
    }

    public function ensureCanConsume(User $user, int $quantity = 1): void
    {
        $summary = $this->summary($user);

        if ($summary['subscription']->status !== 'active') {
            throw new RuntimeException('La cuenta no tiene una suscripcion activa para procesar CVs.');
        }

        if ($summary['remaining'] < $quantity) {
            throw new RuntimeException('Has llegado al limite mensual de CVs de tu plan. Cambia de plan o solicita CVs adicionales para continuar.');
        }
    }

    public function record(User $user, string $type, ?CvProfile $cvProfile = null, array $metadata = [], int $quantity = 1): CvUsageEvent
    {
        $this->ensureCanConsume($user, $quantity);

        $subscription = $this->subscriptionFor($user);

        return $user->cvUsageEvents()->create([
            'cv_usage_subscription_id' => $subscription->id,
            'cv_profile_id' => $cvProfile?->id,
            'type' => $type,
            'quantity' => $quantity,
            'occurred_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }

    public function subscriptionFor(User $user): CvUsageSubscription
    {
        return DB::transaction(function () use ($user): CvUsageSubscription {
            $accountOwner = $this->accountOwnerFor($user);
            $subscription = $accountOwner->cvUsageSubscription()->with('plan')->lockForUpdate()->first();

            if (! $subscription) {
                $subscription = $this->createDefaultSubscription($accountOwner);
            }

            if ($subscription->current_period_ends_at->lte(now())) {
                $subscription = $this->advancePeriod($subscription);
            }

            return $subscription->load('plan');
        });
    }

    public function usedInCurrentPeriod(CvUsageSubscription $subscription): int
    {
        return (int) $subscription->events()
            ->where('occurred_at', '>=', $subscription->current_period_starts_at)
            ->where('occurred_at', '<', $subscription->current_period_ends_at)
            ->sum('quantity');
    }

    private function createDefaultSubscription(User $user): CvUsageSubscription
    {
        $plan = CvUsagePlan::query()
            ->where('slug', 'basico')
            ->where('is_active', true)
            ->firstOrFail();

        $startsAt = now()->startOfDay();

        return $user->cvUsageSubscription()->create([
            'cv_usage_plan_id' => $plan->id,
            'current_period_starts_at' => $startsAt,
            'current_period_ends_at' => $startsAt->copy()->addMonthNoOverflow(),
            'status' => 'active',
        ]);
    }

    public function accountOwnerFor(User $user): User
    {
        if ($user->hasRole('usuario_subordinado')) {
            $owner = $user->accountOwner;

            if (! $owner) {
                throw new RuntimeException('Este usuario no tiene un jefe de cuenta asignado para consumir CVs del plan grupal.');
            }

            return $owner;
        }

        return $user;
    }

    private function advancePeriod(CvUsageSubscription $subscription): CvUsageSubscription
    {
        $startsAt = $subscription->current_period_starts_at->copy();
        $endsAt = $subscription->current_period_ends_at->copy();

        while ($endsAt->lte(now())) {
            $startsAt = $endsAt->copy();
            $endsAt = $endsAt->copy()->addMonthNoOverflow();
        }

        $subscription->update([
            'current_period_starts_at' => $startsAt,
            'current_period_ends_at' => $endsAt,
        ]);

        return $subscription->refresh();
    }

    public function labelForEventType(string $type): string
    {
        return match ($type) {
            CvUsageEvent::TYPE_IMPORT_AI => 'Analisis de CV con IA',
            CvUsageEvent::TYPE_TRANSLATION_AI => 'Traduccion de CV',
            default => $type,
        };
    }

    public function formatPeriodDate(CarbonInterface $date): string
    {
        return Carbon::parse($date)->translatedFormat('d M Y');
    }
}
