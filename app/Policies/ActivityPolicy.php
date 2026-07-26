<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Managers can log an activity on any lead.
     * Reps can only log activities on leads assigned to them.
     */
    public function create(User $user, Lead $lead): bool
    {
        return $user->isManager() || $lead->assigned_to === $user->id;
    }
}
