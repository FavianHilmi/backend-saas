<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class Task extends Model
{
    use HasFactory, BelongsToCompany;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'project_id',
        'user_id',
        'title',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    protected static function booted(): void
    {
        static::updated(function (Task $task) {
            if (Auth::check()) {

                $changes = Arr::except($task->getChanges(), ['updated_at', 'created_at']);

                if (empty($changes) || !is_array($changes)) {
                    return;
                }

                $original = $task->getOriginal();
                $before = is_array($original) ? array_intersect_key($original, $changes) : [];

                AuditLog::create([
                    'company_id' => $task->company_id,
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'model' => 'Task',
                    'model_id' => $task->id,
                    'changes' => [
                        'before' => $before,
                        'after' => $changes,
                    ],
                ]);
            }
        });

        static::deleted(function (Task $task) {
            if (Auth::check()) {
                AuditLog::create([
                    'company_id' => $task->company_id,
                    'user_id' => Auth::id(),
                    'action' => 'deleted',
                    'model' => 'Task',
                    'model_id' => $task->id,
                    'changes' => null,
                ]);
            }
        });
    }
}
