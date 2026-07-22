<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use ApiResponse;

    public function index()
    {
        // otomatis memfilter WHERE company_id = ... dari function Trait BelongsToCompany
        $projects = Project::withCount('tasks')->latest()->get();
        return $this->successResponse($projects, 'Projects Berhasil Diambil');
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return $this->errorResponse('Hanya admin yang bisa membuat project', 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // otomatis inject UUID & company_id dari function Trait BelongsToCompany
        $project = Project::create($validated);

        return $this->successResponse($project, 'Project Berhasil Dibuat', 201);
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return $this->successResponse($project->load('tasks'), 'Berhasil mengambil detail project');
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return $this->successResponse($project, 'Project Berhasil Di-update');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();
        return $this->successResponse(null, 'Project Berhasil Dihapus');
    }
}
