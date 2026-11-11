<?php

declare(strict_types=1);

namespace App\Service;

use App\Models\Passport\RefreshToken;
use App\Models\Passport\Token;
use App\Models\User;

class AppSessionService
{
    /**
     * Revoke the OAuth tokens of apps (e.g. the desktop app) of the given user, including their refresh tokens.
     * Personal access tokens are not affected.
     */
    public function revokeAppSessions(User $user): void
    {
        $tokenIds = Token::query()
            ->where('user_id', '=', $user->getKey())
            ->isApiToken(false)
            ->where('revoked', '=', false)
            ->pluck('id');

        foreach ($tokenIds->chunk(1000) as $chunk) {
            Token::query()->whereIn('id', $chunk)->update(['revoked' => true]);
            RefreshToken::query()->whereIn('access_token_id', $chunk)->update(['revoked' => true]);
        }
    }
}
