<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Jobs\SendTaskAssignedNotification;
use Illuminate\Validation\Rule;


class TaskController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $query = Task::whereHas('project', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->with(['project', 'assignee']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $tasks = $query->latest()->get();

        return $this->successResponse($tasks, 'Berhasil mengambil daftar task');
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'project_id' => [
                'required',
                Rule::exists('projects', 'id')->where(fn($q) => $q->where('company_id', $companyId))
            ],
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn($q) => $q->where('company_id', $companyId))
            ],
            'title' => 'required|string|max:255',
            'status' => 'nullable|in:todo,in_progress,done',
        ]);

        $task = Task::create($validated);

        if ($task->user_id) {
            SendTaskAssignedNotification::dispatch($task);
        }
        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil membuat task', 201);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil mengambil detail task');
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $user = $request->user();
        $companyId = $user->company_id;

        $validated = $request->validate([
            'project_id' => [
                'sometimes',
                'required',
                Rule::exists('projects', 'id')->where(fn($q) => $q->where('company_id', $companyId))
            ],
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

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();
        return $this->successResponse(null, 'Berhasil menghapus task');
    }
}
