<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            //
        });

        // A request on an untrusted host (see App\Http\Middleware\TrustHosts)
        // otherwise renders as a bare "Bad request." 400. Show a message that
        // says how to fix it instead. The framework has already converted the
        // SuspiciousOperationException into a BadRequestHttpException by the time
        // renderables run, so we match that and inspect the original.
        $this->renderable(function (BadRequestHttpException $e, Request $request): ?Response {
            $previous = $e->getPrevious();

            if (! $previous instanceof SuspiciousOperationException
                || ! str_starts_with($previous->getMessage(), 'Untrusted Host')) {
                return null; // any other bad request keeps the default response
            }

            $message = 'This hostname is not configured for this instance. '
                .'Set APP_URL, or add the host to TRUSTED_HOSTS.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 400);
            }

            return response()->view('errors.untrusted-host', ['message' => $message], 400);
        });
    }

    public function render($request, Throwable $e): Response|RedirectResponse
    {
        $response = parent::render($request, $e);

        if ($response->getStatusCode() === 419) {
            return back()->with([
                'message' => 'The page expired, please try again.',
            ]);
        }

        return $response;
    }
}
