<?php

namespace App\Policies;

use App\Models\CvProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CvProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CvProfile $cvProfile): bool
    {
        return $user->canViewCvOwner($cvProfile->user_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CvProfile $cvProfile): bool
    {
        return $cvProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CvProfile $cvProfile): bool
    {
        return $cvProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CvProfile $cvProfile): bool
    {
        return $cvProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CvProfile $cvProfile): bool
    {
        return $cvProfile->user_id === $user->id;
    }
}
