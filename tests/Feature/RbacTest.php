<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $admin;
    protected $memberA;
    protected $memberB;
    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        /*
        Setup company, project, admin & members untuk test RBAC
        */

        $this->company = Company::create(['name' => 'Nexus Company']);
        $this->project = Project::create([
            'company_id' => $this->company->id,
            'name' => 'Project Portal',
            'description' => 'Redesain interface portal',
        ]);

        $this->admin = User::create([
            'company_id' => $this->company->id,
            'name' => 'Admin User',
            'email' => 'admin@nexus.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->memberA = User::create([
            'company_id' => $this->company->id,
            'name' => 'Member A',
            'email' => 'membera@nexus.com',
            'password' => bcrypt('password123'),
            'role' => 'member',
        ]);

        $this->memberB = User::create([
            'company_id' => $this->company->id,
            'name' => 'Member B',
            'email' => 'memberb@nexus.com',
            'password' => bcrypt('password123'),
            'role' => 'member',
        ]);
    }

    public function test_member_can_update_their_own_assigned_task()
    {
        // Assign task ke Member A
        $task = Task::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'user_id' => $this->memberA->id,
            'title' => 'Task Member A',
            'status' => 'todo',
        ]);

        // update task milik Member A -> EXPECTED BERHASIL (200)
        $response = $this->actingAs($this->memberA, 'sanctum')
            ->putJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'status' => 'in_progress',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_member_cannot_update_other_members_task()
    {
        // Assign task ke Member A
        $task = Task::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'user_id' => $this->memberA->id,
            'title' => 'Task Member A',
            'status' => 'todo',
        ]);

        // Member B coba update task milik Member A -> EXPECTED DITOLAK (403)
        $response = $this->actingAs($this->memberB, 'sanctum')
            ->putJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'status' => 'done',
            ]);

        $response->assertStatus(403);
    }

    public function test_member_cannot_delete_any_task()
    {
        // Task di-assign ke Member A
        $task = Task::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'user_id' => $this->memberA->id,
            'title' => 'Task Member A',
            'status' => 'todo',
        ]);

        // Member A coba delete task -> EXPECTED DITOLAK (403)
        $response = $this->actingAs($this->memberA, 'sanctum')
            ->deleteJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_member_cannot_create_project()
    {
        $response = $this->actingAs($this->memberA, 'sanctum')
            ->postJson('/api/v1/projects', [
                'name' => 'Create Project Test',
                'description' => 'Mencoba buat project',
            ]);

        $response->assertStatus(403);
    }

    public function test_member_cannot_delete_project()
    {
        $response = $this->actingAs($this->memberA, 'sanctum')
            ->deleteJson("/api/v1/projects/{$this->project->id}");

        $response->assertStatus(403);
    }

    public function test_member_cannot_reassign_task_to_another_user()
    {
        $task = Task::create([
            'company_id' => $this->company->id,
            'project_id' => $this->project->id,
            'user_id' => $this->memberA->id,
            'title' => 'Task Member A',
            'status' => 'todo',
        ]);

        // Member A ganti assign task ke Member B
        $response = $this->actingAs($this->memberA, 'sanctum')
            ->putJson("/api/v1/projects/{$this->project->id}/tasks/{$task->id}", [
                'user_id' => $this->memberB->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_member_cannot_register_new_member()
    {
        $response = $this->actingAs($this->memberA, 'sanctum')
            ->postJson('/api/v1/registerMember', [
                'name' => 'Member Baru',
                'email' => 'newmember@nexus.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(403);
    }
}
