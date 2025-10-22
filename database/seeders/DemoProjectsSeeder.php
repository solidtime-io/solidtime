<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoProjectsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure a demo organization exists
        $organization = Organization::query()->first() ?? Organization::factory()->create([
            'name' => 'PiaDesign Demo',
        ]);
        $client = Client::query()->whereBelongsTo($organization, 'organization')->first() ?? Client::factory()->for($organization, 'organization')->create([
            'name' => 'PiaDesign Client',
        ]);

        $projects = [
            'Hendrick Avenue',
            'Hill Rise',
            'Shaftesbury Villas 3',
            'Shaftesbury Villas 4',
            'Hotham Road',
            'Manor Road',
            'Holly House',
        ];

        foreach ($projects as $name) {
            Project::query()->firstOrCreate([
                'organization_id' => $organization->getKey(),
                'name' => $name,
            ], [
                'client_id' => $client->getKey(),
                'color' => '#3b82f6',
            ]);
        }
    }
}
