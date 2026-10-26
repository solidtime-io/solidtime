<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(User::class)]
class UserTest extends TestCase
{
    public function test_is_super_admin_is_true_for_configured_and_verified_email(): void
    {
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->make([
            'email' => 'admin@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->assertTrue($user->isSuperAdmin());
    }

    public function test_is_super_admin_is_false_when_email_is_not_verified(): void
    {
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->make([
            'email' => 'admin@example.com',
            'email_verified_at' => null,
        ]);

        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_is_super_admin_is_false_for_a_non_configured_email(): void
    {
        Config::set('auth.super_admins', ['admin@example.com']);
        $user = User::factory()->make([
            'email' => 'someone@example.com',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_has_local_password_reflects_password_presence(): void
    {
        $withPassword = User::factory()->make(['password' => bcrypt('secret-password')]);
        $passwordless = User::factory()->make(['password' => null]);

        $this->assertTrue($withPassword->hasLocalPassword());
        $this->assertFalse($passwordless->hasLocalPassword());
    }
}
