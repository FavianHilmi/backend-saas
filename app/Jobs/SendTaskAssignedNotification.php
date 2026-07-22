<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTaskAssignedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function handle(): void
    {
        Log::info(" [QUEUE JOB] Notifikasi Task Assigned Terkirim!", [
            'task_id'    => $this->task->id,
            'task_title' => $this->task->title,
            'assigned_to'=> $this->task->user_id,
            'company_id' => $this->task->company_id,
        ]);
    }
}
