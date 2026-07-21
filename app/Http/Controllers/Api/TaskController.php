<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Task::with(['project', 'assignee']);

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
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'user_id'    => 'nullable|exists:users,id',
            'title'      => 'required|string|max:255',
            'status'     => 'nullable|in:todo,in_progress,done',
        ]);

        $task = Task::create($validated);

        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil membuat task', 201);
    }

    public function show(Task $task)
    {
        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil mengambil detail task');
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'project_id' => 'sometimes|required|exists:projects,id',
            'user_id'    => 'nullable|exists:users,id',
            'title'      => 'sometimes|required|string|max:255',
            'status'     => 'sometimes|required|in:todo,in_progress,done',
        ]);

        $task->update($validated);

        return $this->successResponse($task->load(['project', 'assignee']), 'Berhasil memperbarui task');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return $this->successResponse(null, 'Berhasil menghapus task');
    }
}
