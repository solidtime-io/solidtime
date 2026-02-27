<?php

declare(strict_types=1);

namespace Extensions\Linear\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LinearGraphQLClient
{
    private const ENDPOINT = 'https://api.linear.app/graphql';

    public function __construct(
        private readonly string $accessToken,
    ) {}

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    public function query(string $query, array $variables = []): array
    {
        $payload = ['query' => $query];
        if ($variables !== []) {
            $payload['variables'] = $variables;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post(self::ENDPOINT, $payload);

        $json = $response->json();

        if (isset($json['errors']) && count($json['errors']) > 0) {
            throw new RuntimeException($json['errors'][0]['message']);
        }

        return $json['data'];
    }
}
