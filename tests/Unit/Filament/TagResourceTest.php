<?php

declare(strict_types=1);

namespace Tests\Unit\Filament;

use App\Filament\Resources\TagResource;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class TagResourceTest extends FilamentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->withPersonalOrganization()->create([
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($user);
    }

    public function test_can_list_tags(): void
    {
        // Arrange
        $tags = Tag::factory()->createMany(5);

        // Act
        $response = Livewire::test(TagResource\Pages\ListTags::class);

        // Assert
        $response->assertSuccessful();
        $response->assertCanSeeTableRecords($tags);
    }
}
