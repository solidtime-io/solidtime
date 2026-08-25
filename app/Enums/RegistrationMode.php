<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationMode: string
{
    case On = 'on';
    case InviteOnly = 'invite-only';
    case Off = 'off';

    public static function fromConfig(mixed $value): self
    {
        if ($value === true) {
            return self::On;
        }

        if ($value === false || $value === null) {
            return self::Off;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'on', 'true' => self::On,
            'invite', 'invite-only' => self::InviteOnly,
            default => self::Off,
        };
    }
}
