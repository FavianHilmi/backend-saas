<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendTaskAssignedNotification implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue, Dispatchable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Task $task) {}

    public function handle(): void
    {
        if (!$this->task) {
            return;
        }

        Log::info(" [QUEUE JOB] Notifikasi Task Assigned Terkirim", [
            'task_id'     => $this->task->id,
            'task_title'  => $this->task->title,
            'assigned_to' => $this->task->user_id,
            'company_id'  => $this->task->company_id,
        ]);
    }
}
