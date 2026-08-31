<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RegistrationMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegistrationMode::class)]
class RegistrationModeTest extends TestCase
{
    /**
     * @return array<string, array{mixed, RegistrationMode}>
     */
    public static function configValueProvider(): array
    {
        return [
            'boolean true' => [true, RegistrationMode::On],
            'on' => ['on', RegistrationMode::On],
            'true' => ['true', RegistrationMode::On],
            'invite-only' => ['invite-only', RegistrationMode::InviteOnly],
            'boolean false' => [false, RegistrationMode::Off],
            'off' => ['off', RegistrationMode::Off],
            'false' => ['false', RegistrationMode::Off],
            'unsupported invite alias' => ['invite', RegistrationMode::Off],
            'unknown' => ['unknown', RegistrationMode::Off],
        ];
    }

    #[DataProvider('configValueProvider')]
    public function test_registration_mode_is_created_from_config_value(mixed $value, RegistrationMode $expected): void
    {
        $this->assertSame($expected, RegistrationMode::fromConfig($value));
    }
}
