<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected $nexusCompany;
    protected $userNexus;
    protected $vertexCompany;
    protected $userVertex;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup perusqhaan 1
        $this->nexusCompany = Company::create(['name' => 'Nexus Company']);
        $this->userNexus = User::create([
            'company_id' => $this->nexusCompany->id,
            'name' => 'Nexus Admin',
            'email' => 'admin@nexus.com',
            'password' => bcrypt('admin#123'),
            'role' => 'admin',
        ]);

        // Setup perusahaan 2
        $this->vertexCompany = Company::create(['name' => 'Vertex Company']);
        $this->userVertex = User::create([
            'company_id' => $this->vertexCompany->id,
            'name' => 'Vertex Admin',
            'email' => 'admin@vertex.com',
            'password' => bcrypt('admin#123'),
            'role' => 'admin',
        ]);
    }

    public function test_user_can_only_see_their_own_company_projects()
    {
        // $this->withoutExceptionHandling();
        // create project di Company 1 & 2
        Project::create([
            'company_id' => $this->nexusCompany->id,
            'name' => 'Project Nexus',
            'description' => 'Deskripsi Project Nexus',
        ]);

        Project::create([
            'company_id' => $this->vertexCompany->id,
            'name' => 'Project Vertex',
            'description' => 'Deskripsi Project Vertex',
        ]);

        // Login sebagai user dari Company 1
        $response = $this->actingAs($this->userNexus, 'sanctum')
            ->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Project Nexus'])
            ->assertJsonMissing(['name' => 'Project Vertex']);
    }

    public function test_user_cannot_access_other_company_task_details()
    {
        // Project & Task milik Company 2
        $projectB = Project::create(['company_id' => $this->vertexCompany->id, 'name' => 'Project Vertex']);
        $taskB = Task::create([
            'company_id' => $this->vertexCompany->id,
            'project_id' => $projectB->id,
            'title' => 'Secret Task Company Vertex',
        ]);

        // User dari Company 1 mencoba akses Task milik Company 2
        $response = $this->actingAs($this->userNexus, 'sanctum')
            ->getJson("/api/v1/projects/{$projectB->id}/tasks/{$taskB->id}");

        $response->assertStatus(404);
    }

    public function test_user_cannot_update_or_delete_other_company_project()
    {
        $projectVertex = Project::create([
            'company_id' => $this->vertexCompany->id,
            'name' => 'Project Test Vertex',
        ]);

        $updateResponse = $this->actingAs($this->userNexus, 'sanctum')
            ->putJson("/api/v1/projects/{$projectVertex->id}", [
                'name' => 'Test Hack Update',
            ]);
        $updateResponse->assertStatus(404);

        $deleteResponse = $this->actingAs($this->userNexus, 'sanctum')
            ->deleteJson("/api/v1/projects/{$projectVertex->id}");
        $deleteResponse->assertStatus(404);
    }

    public function test_user_cannot_create_task_in_other_company_project()
    {
        $projectId = Project::create([
            'company_id' => $this->vertexCompany->id,
            'name' => 'Project Test Vertex',
        ]);

        $response = $this->actingAs($this->userNexus, 'sanctum')
            ->postJson("/api/v1/projects/{$projectId->id}/tasks", [
                'title' => 'Test Task Ilegal',
            ]);

        $response->assertStatus(404);
    }
}
