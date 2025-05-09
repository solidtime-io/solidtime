<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Service\CurrencyService;
use Brick\Money\ISOCurrencyProvider;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    /**
     * Get all currencies
     *
     * @response array{code: string, name: string, symbol: string}[]
     *
     * @operationId getCurrencies
     */
    public function index(): JsonResponse
    {
        $currencyService = app(CurrencyService::class);

        $currencies = array_values(array_map(
            fn ($currency) => [
                'code' => $currency->getCurrencyCode(),
                'name' => $currency->getName(),
                'symbol' => $currencyService->getCurrencySymbol($currency->getCurrencyCode()),
            ],
            ISOCurrencyProvider::getInstance()->getAvailableCurrencies()
        ));

        return response()->json($currencies);
    }
}
