<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\CurrencyRule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(CurrencyRule::class)]
class CurrencyRuleTest extends TestCase
{
    public function test_validation_passes_if_value_is_valid_currency_code(): void
    {
        // Arrange
        $validator = Validator::make([
            'currency' => 'EUR',
        ], [
            'currency' => [new CurrencyRule],
        ]);

        // Act
        $isValid = $validator->passes();
        $messages = $validator->messages()->toArray();

        // Assert
        $this->assertTrue($isValid);
        $this->assertArrayNotHasKey('currency', $messages);
    }

    public function test_validation_fails_if_value_is_not_a_string(): void
    {
        // Arrange
        $validator = Validator::make([
            'currency' => true,
        ], [
            'currency' => [new CurrencyRule],
        ]);

        // Act
        $isValid = $validator->passes();
        $messages = $validator->messages()->toArray();

        // Assert
        $this->assertFalse($isValid);
        $this->assertEquals('The currency field must be a string.', $messages['currency'][0]);
    }

    public function test_validation_fails_if_value_is_not_a_valid_currency(): void
    {
        // Arrange
        $validator = Validator::make([
            'currency' => 'XXX',
        ], [
            'currency' => [new CurrencyRule],
        ]);

        // Act
        $isValid = $validator->passes();
        $messages = $validator->messages()->toArray();

        // Assert
        $this->assertFalse($isValid);
        $this->assertEquals('The currency field must be a valid currency code (ISO 4217).', $messages['currency'][0]);
    }

    public function test_validation_fails_if_value_is_lower_case(): void
    {
        // Arrange
        $validator = Validator::make([
            'currency' => 'eur',
        ], [
            'currency' => [new CurrencyRule],
        ]);

        // Act
        $isValid = $validator->passes();
        $messages = $validator->messages()->toArray();

        // Assert
        $this->assertFalse($isValid);
        $this->assertEquals('The currency field must be a valid currency code (ISO 4217).', $messages['currency'][0]);
    }
}
