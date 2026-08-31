<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\Handler;
use League\OAuth2\Server\Exception\OAuthServerException;
use RuntimeException;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    public function test_oauth_access_denied_exceptions_are_not_reported(): void
    {
        // Arrange
        $exception = OAuthServerException::accessDenied(
            'Access token could not be verified',
            previous: new RuntimeException('The token is expired')
        );

        // Act
        $shouldReport = app(Handler::class)->shouldReport($exception);

        // Assert
        $this->assertFalse($shouldReport);
    }

    public function test_operational_oauth_exceptions_are_reported(): void
    {
        // Arrange
        $exception = OAuthServerException::serverError('Signing key could not be read');

        // Act
        $shouldReport = app(Handler::class)->shouldReport($exception);

        // Assert
        $this->assertTrue($shouldReport);
    }
}
