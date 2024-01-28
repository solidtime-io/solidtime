<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Illuminate\Support\Facades\Config;

class UserModelTest extends ModelTestAbstract
{
    public function test_normal_user_can_not_access_admin_panel(): void
    {
        // Arrange
        Config::set('auth.super_admins', ['some@email.test', 'other@email.test']);
        $user = User::factory()->create();
        $panelProvider = new AdminPanelProvider(app());
        $mainPanel = $panelProvider->panel(Panel::make());

        // Act
        $canAccess = $user->canAccessPanel($mainPanel);

        // Assert
        $this->assertFalse($canAccess);
    }

    public function test_user_in_super_admin_config_can_access_admin_panel(): void
    {
        // Arrange
        Config::set('auth.super_admins', ['some@email.test', 'other@email.test']);
        $user = User::factory()->create([
            'email' => 'some@email.test',
        ]);
        $panelProvider = new AdminPanelProvider(app());
        $mainPanel = $panelProvider->panel(Panel::make());

        // Act
        $canAccess = $user->canAccessPanel($mainPanel);

        // Assert
        $this->assertTrue($canAccess);
    }
}
