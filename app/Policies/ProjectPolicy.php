<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    private function belongsToSameCompany(User $user, Project $project): bool
    {
        return $user->company_id === $project->company_id;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->belongsToSameCompany($user, $project);
    }

    public function update(User $user, Project $project): bool
    {
        return $this->belongsToSameCompany($user, $project) && $user->role === 'admin';
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->belongsToSameCompany($user, $project) && $user->role === 'admin';
    }
}
