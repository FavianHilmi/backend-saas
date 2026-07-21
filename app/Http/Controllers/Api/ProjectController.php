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
        return $this->successResponse($projects, 'Projects retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // otomatis inject UUID & company_id dari function Trait BelongsToCompany
        $project = Project::create($validated);

        return $this->successResponse($project, 'Project Berhasil Dibuat', 201);
    }

    public function show(Project $project)
    {
        // Route Model Binding + Global Scope supaya tidak bisa akses project dari company lain
        return $this->successResponse($project->load('tasks'), 'Berhasil mengambil detail project');
    }   

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return $this->successResponse($project, 'Project updated successfully');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return $this->successResponse(null, 'Project deleted successfully');
    }
}
