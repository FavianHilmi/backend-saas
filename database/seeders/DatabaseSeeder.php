<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------------
        // Company 1: Nexus Company (Tenant A)
        // -------------------------------------------------------------
        $nexus = Company::create(['name' => 'Nexus Company']);

        $nexusAdmin = User::create([
            'company_id' => $nexus->id,
            'name'       => 'Nexus Admin',
            'email'      => 'admin@nexus.com',
            'password'   => Hash::make('admin#123'),
            'role'       => 'admin',
        ]);

        $nexusMember = User::create([
            'company_id' => $nexus->id,
            'name'       => 'Nexus Member',
            'email'      => 'member@nexus.com',
            'password'   => Hash::make('member#123'),
            'role'       => 'member',
        ]);

        $projectNexus = Project::create([
            'company_id'  => $nexus->id,
            'name'        => 'Project Portal Redesign',
            'description' => 'Modernisasi tampilan portal perusahaan',
        ]);

        Task::create([
            'company_id' => $nexus->id,
            'project_id' => $projectNexus->id,
            'user_id'    => $nexusMember->id,
            'title'      => 'Setup Autentikasi API',
            'status'     => 'in_progress',
        ]);

        // -------------------------------------------------------------
        // Company 2: Vertex Company (Tenant B)
        // -------------------------------------------------------------
        $vertex = Company::create(['name' => 'Vertex Company']);

        $vertexAdmin = User::create([
            'company_id' => $vertex->id,
            'name'       => 'Vertex Admin',
            'email'      => 'admin@vertex.com',
            'password'   => Hash::make('admin#123'),
            'role'       => 'admin',
        ]);

        $projectVertex = Project::create([
            'company_id'  => $vertex->id,
            'name'        => 'Mobile App Launch',
            'description' => 'Prepare launch aplikasi mobile baru',
        ]);

        Task::create([
            'company_id' => $vertex->id,
            'project_id' => $projectVertex->id,
            'user_id'    => $vertexAdmin->id,
            'title'      => 'Review Desain UI/UX',
            'status'     => 'todo',
        ]);
    }
}
