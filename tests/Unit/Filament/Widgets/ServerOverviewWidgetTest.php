<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\ServerOverview;
use App\Models\User;
use Cache;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\Unit\Filament\FilamentTestCase;

#[UsesClass(ServerOverview::class)]
class ServerOverviewWidgetTest extends FilamentTestCase
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

    public function test_shows_version_and_build_it_no_information_about_the_current_version_exists(): void
    {
        // Arrange
        Config::set('app.version', '1.0.0');
        Config::set('app.build', 'ABC123');
        Cache::forget('latest_version');

        // Act
        $response = Livewire::test(ServerOverview::class);

        // Assert
        $response->assertSuccessful();
        $response->assertSee('1.0.0');
        $response->assertSee('ABC123');
        $response->assertDontSee('Update available');
        $response->assertDontSee('Current version');
    }

    public function test_show_version_is_current_when_the_latest_version_is_the_same_as_the_current_version(): void
    {
        // Arrange
        Config::set('app.version', '1.0.0');
        Config::set('app.build', 'ABC123');
        Cache::put('latest_version', '1.0.0');

        // Act
        $response = Livewire::test(ServerOverview::class);

        // Assert
        $response->assertSuccessful();
        $response->assertSee('1.0.0');
        $response->assertSee('ABC123');
        $response->assertDontSee('Update available');
        $response->assertSee('Current version');
    }

    public function test_shows_update_available(): void
    {
        // Arrange
        Config::set('app.version', '1.0.0');
        Config::set('app.build', 'ABC123');
        Cache::put('latest_version', '1.0.1');

        // Act
        $response = Livewire::test(ServerOverview::class);

        // Assert
        $response->assertSuccessful();
        $response->assertSee('1.0.0');
        $response->assertSee('ABC123');
        $response->assertSee('Update available');
        $response->assertDontSee('Current version');
        $response->assertSee('1.0.1');
    }
}
