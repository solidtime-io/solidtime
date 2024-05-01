<?php

declare(strict_types=1);

namespace App\Extensions\Fortify;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Http\Responses\LoginResponse;
use Symfony\Component\HttpFoundation\Response;

class CustomLoginResponse extends LoginResponse
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
            ? response()->json(['two_factor' => false])
            : Inertia::location($redirectPath);
    }
}
