<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class AuthenticateFilamentPanel extends Middleware
{
    /**
     * The admin panel has no login page of its own (it shares the app's
     * session-based login), so Filament::getLoginUrl() always returns null.
     * Fall back to the app's login route so guests are redirected instead
     * of receiving an empty 401 response.
     */
    protected function redirectTo($request): ?string
    {
        return parent::redirectTo($request) ?? ($request->expectsJson() ? null : route('login'));
    }
}
