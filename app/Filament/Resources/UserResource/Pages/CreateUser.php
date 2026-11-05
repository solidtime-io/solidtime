<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\Weekday;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Service\UserService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): User
    {
        $userService = app(UserService::class);
        $user = $userService->createUser(
            name: $data['name'],
            email: $data['email'],
            password: $data['password_create'],
            timezone: $data['timezone'],
            weekStart: $data['week_start'] instanceof Weekday ? $data['week_start'] : Weekday::from($data['week_start']),
            currency: $data['currency'],
            verifyEmail: (bool) $data['is_email_verified'],
        );

        return $user;
    }
}
