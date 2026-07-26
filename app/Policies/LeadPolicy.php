<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Any authenticated user can list leads.
     * The controller scopes the query to the rep's own leads when needed.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Managers can view any lead. Reps can only view leads assigned to them.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $user->isManager() || $lead->assigned_to === $user->id;
    }

    /**
     * Any authenticated user can create a lead.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Managers can update any lead. Reps can only update leads assigned to them.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $user->isManager() || $lead->assigned_to === $user->id;
    }

    /**
     * Only managers can assign a lead to a rep.
     */
    public function assign(User $user): bool
    {
        return $user->isManager();
    }
}
