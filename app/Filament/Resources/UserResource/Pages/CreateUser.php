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
            $data['name'],
            $data['email'],
            $data['password_create'],
            $data['timezone'],
            Weekday::from($data['week_start']),
            $data['currency'],
            (bool) $data['is_email_verified']
        );

        return $user;
    }
}
