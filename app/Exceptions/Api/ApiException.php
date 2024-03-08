<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

abstract class ApiException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()
            ->json([
                'error' => true,
                'key' => $this->getKey(),
                'message' => $this->getTranslatedMessage(),
            ], 400);
    }

    /**
     * Get the key for the exception.
     */
    public function getKey(): string
    {
        if (defined(static::class.'::KEY')) {
            return static::KEY;
        }

        throw new LogicException('API exceptions need the KEY constant defined.');
    }

    /**
     * Get the translated message for the exception.
     */
    public function getTranslatedMessage(): string
    {
        return __('exceptions.api.'.$this->getKey());
    }
}
