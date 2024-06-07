<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

abstract class ApiException extends Exception
{
    public const string KEY = 'api_exception';

    public function __construct()
    {
        parent::__construct(static::KEY);
    }

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
        $key = static::KEY;

        if ($key === ApiException::KEY) {
            throw new LogicException('API exceptions need the KEY constant defined.');
        }

        return $key;
    }

    /**
     * Get the translated message for the exception.
     */
    public function getTranslatedMessage(): string
    {
        return __('exceptions.api.'.$this->getKey());
    }

    /**
     * Report the exception.
     *
     * @return bool true means the exception handler will not report it again
     */
    public function report(): bool
    {
        // TODO: temporary activated
        return false;
    }
}
