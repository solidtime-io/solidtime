<?php

declare(strict_types=1);

namespace Extensions\Linear\Tests\Unit\Services;

use Extensions\Linear\Services\LinearGraphQLClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinearGraphQLClientTest extends TestCase
{
    public function test_query_sends_graphql_request_with_auth_header(): void
    {
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => ['viewer' => ['id' => 'user-123', 'name' => 'Test User']],
            ], 200),
        ]);

        $client = new LinearGraphQLClient('test-access-token');
        $result = $client->query('{ viewer { id name } }');

        $this->assertEquals('user-123', $result['viewer']['id']);
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'test-access-token')
                && $request->url() === 'https://api.linear.app/graphql';
        });
    }

    public function test_query_with_variables(): void
    {
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'data' => ['issues' => ['nodes' => []]],
            ], 200),
        ]);

        $client = new LinearGraphQLClient('token');
        $result = $client->query('query ($id: String!) { issues(filter: { assignee: { id: { eq: $id } } }) { nodes { id } } }', ['id' => 'user-1']);

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return isset($body['variables']['id']) && $body['variables']['id'] === 'user-1';
        });
    }

    public function test_query_throws_on_graphql_errors(): void
    {
        Http::fake([
            'api.linear.app/graphql' => Http::response([
                'errors' => [['message' => 'Not authorized']],
            ], 200),
        ]);

        $client = new LinearGraphQLClient('bad-token');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not authorized');
        $client->query('{ viewer { id } }');
    }
}
