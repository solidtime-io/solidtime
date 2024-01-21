<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;

class UserModelTest extends ModelTestAbstract
{
    public function test_normal_user_can_not_access_admin_panel(): void
    {
        // Arrange
        $user = User::factory()->create();
        $panelProvider = new AdminPanelProvider(app());
        $mainPanel = $panelProvider->panel(Panel::make());

        // Act
        $canAccess = $user->canAccessPanel($mainPanel);

        // Assert
        $this->assertFalse($canAccess);
    }
}
