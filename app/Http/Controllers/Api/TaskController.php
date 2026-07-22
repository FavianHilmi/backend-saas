<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Jobs\SendTaskAssignedNotification;
use Illuminate\Validation\Rule;


class TaskController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $query = $project->tasks()->with(['project', 'assignee']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->latest()->get();

        return $this->successResponse($tasks, 'Berhasil mengambil daftar task');
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('company_id', $companyId))
            ],
            'title' => 'required|string|max:255',
            'status' => 'nullable|in:todo,in_progress,done',
        ]);

        $task = $project->tasks()->create($validated);

        if ($task->user_id) {
            SendTaskAssignedNotification::dispatch($task);
        }
        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil membuat task', 201);
    }

    public function show(Project $project, Task $task)
    {
        $this->authorize('view', $task);

        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil mengambil detail task');
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $user = $request->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('company_id', $companyId))
            ],
            'title' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:todo,in_progress,done',
        ]);

        if ($user->role === 'member' && isset($validated['user_id']) && $validated['user_id'] != $task->user_id) {
            return $this->errorResponse('Member tidak diizinkan mengubah assignee/penanggung jawab task', 403);
        }

        $oldUserId = $task->user_id;
        $task->update($validated);

        if ($task->user_id && $task->user_id !== $oldUserId) {
            SendTaskAssignedNotification::dispatch($task);
        }

        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil memperbarui task');
    }

    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();
        return $this->successResponse(null, 'Berhasil menghapus task');
    }
}
