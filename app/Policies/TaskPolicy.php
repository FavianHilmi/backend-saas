<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{

    private function belongsToSameCompany(User $user, Task $task): bool
    {
        return $user->company_id === $task->project->company_id;
    }

    public function view(User $user, Task $task): bool
    {
        if (!$this->belongsToSameCompany($user, $task)) {
            return false;
        }

        return true;
    }

    public function update(User $user, Task $task): bool
    {
        if (!$this->belongsToSameCompany($user, $task)) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'member' && $task->user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->belongsToSameCompany($user, $task) && $user->role === 'admin';
    }
}
