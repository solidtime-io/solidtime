<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\CurrencyController;
use App\Service\CurrencyService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(CurrencyController::class)]
#[CoversClass(CurrencyService::class)]
#[UsesClass(CurrencyController::class)]
class CurrencyEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_return_list_of_available_currencies_incl_symbol(): void
    {
        // Arrange

        // Act
        $response = $this->getJson(route('api.v1.currencies.index'));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(166);
        $responseObj = collect($response->json());
        $this->assertSame([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
        ], $responseObj->firstWhere('code', '=', 'EUR'));
    }
}
