<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\ColorRule;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCase;

#[CoversClass(ColorRule::class)]
#[UsesClass(ColorRule::class)]
class ColorRuleTest extends TestCase
{
    public function test_validation_passes_if_value_is_valid_color(): void
    {
        // Arrange
        $validator = Validator::make([
            'color' => '#ef5350',
        ], [
            'color' => [new ColorRule()],
        ]);

        // Act
        $isValid = $validator->passes();
        $messages = $validator->messages()->toArray();

        // Assert
        $this->assertTrue($isValid);
        $this->assertArrayNotHasKey('color', $messages);
    }

    public function test_validation_fails_if_value_is_not_a_string(): void
    {
        // Arrange
        $validator = Validator::make([
            'color' => true,
        ], [
            'color' => [new ColorRule()],
        ]);

        // Act
        $isValid = $validator->passes();
        $messages = $validator->messages()->toArray();

        // Assert
        $this->assertFalse($isValid);
        $this->assertEquals('The color field must be a string.', $messages['color'][0]);
    }

    public function test_validation_fails_if_value_is_not_a_valid_color(): void
    {
        // Arrange
        $validator = Validator::make([
            'color' => 'rgb(0,0,0)',
        ], [
            'color' => [new ColorRule()],
        ]);

        // Act
        $isValid = $validator->passes();
        $messages = $validator->messages()->toArray();

        // Assert
        $this->assertFalse($isValid);
        $this->assertEquals('The color field must be a valid color.', $messages['color'][0]);
    }
}
