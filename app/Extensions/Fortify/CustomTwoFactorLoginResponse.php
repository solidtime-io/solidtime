<?php

declare(strict_types=1);

namespace App\Extensions\Fortify;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class CustomTwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $redirectPath = session()->pull('url.intended', route('dashboard', [], false));

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : Inertia::location($redirectPath);
    }
}
