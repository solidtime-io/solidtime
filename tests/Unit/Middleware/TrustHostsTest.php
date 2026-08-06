<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TrustHosts;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[CoversClass(TrustHosts::class)]
class TrustHostsTest extends TestCase
{
    private const string CANONICAL = 'https://app.example.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => self::CANONICAL]);
    }

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]); // don't leak static state between tests
        parent::tearDown();
    }

    /**
     * The real middleware, with only the environment gate forced on (it
     * self-exempts in the testing environment).
     */
    private function middleware(): TrustHosts
    {
        return new class($this->app) extends TrustHosts
        {
            protected function shouldSpecifyTrustedHosts(): bool
            {
                return true;
            }
        };
    }

    private function accepts(Request $request): bool
    {
        $this->middleware()->handle($request, fn (Request $request): Response => new Response('passed'));

        try {
            dump($request->getHost());

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function test_canonical_host_is_accepted(): void
    {
        // Arrange
        $request = Request::create(self::CANONICAL.'/login');

        // Act
        $accepted = $this->accepts($request);

        // Assert
        $this->assertTrue($accepted);
    }

    public function test_subdomain_of_canonical_host_is_accepted(): void
    {
        // Arrange
        $request = Request::create('https://team.app.example.com/login');

        // Act
        $accepted = $this->accepts($request);

        // Assert
        $this->assertTrue($accepted);
    }

    public function test_declared_trusted_host_is_accepted(): void
    {
        // Arrange
        config(['app.trusted_hosts' => ['box.tailnet.ts.net']]);
        $request = Request::create('https://box.tailnet.ts.net/login');

        // Act
        $accepted = $this->accepts($request);

        // Assert
        $this->assertTrue($accepted);
    }

    public function test_wildcard_trusted_host_matches_subdomains_only(): void
    {
        // Arrange
        config(['app.trusted_hosts' => ['*.example.net']]);
        $subdomainRequest = Request::create('https://foo.example.net/login');
        $nestedSubdomainRequest = Request::create('https://a.b.example.net/login');
        $apexRequest = Request::create('https://example.net/login');
        $suffixInjectionRequest = Request::create('https://example.net.evil.com/login');

        // Act
        $subdomainAccepted = $this->accepts($subdomainRequest);
        $nestedSubdomainAccepted = $this->accepts($nestedSubdomainRequest);
        $apexAccepted = $this->accepts($apexRequest);
        $suffixInjectionAccepted = $this->accepts($suffixInjectionRequest);

        // Assert
        $this->assertTrue($subdomainAccepted);
        $this->assertTrue($nestedSubdomainAccepted);
        $this->assertFalse($apexAccepted);
        $this->assertFalse($suffixInjectionAccepted);
    }

    public function test_multiple_trusted_hosts_are_all_accepted(): void
    {
        // Arrange
        config(['app.trusted_hosts' => [
            'box.tailnet.ts.net',
            'solidtime.internal',
            '*.preview.example.com',
        ]]);
        $tailnetRequest = Request::create('https://box.tailnet.ts.net/login');
        $internalRequest = Request::create('https://solidtime.internal/login');
        $previewRequest = Request::create('https://pr-42.preview.example.com/login');
        $unlistedRequest = Request::create('https://evil.example.com/login');

        // Act
        $tailnetAccepted = $this->accepts($tailnetRequest);
        $internalAccepted = $this->accepts($internalRequest);
        $previewAccepted = $this->accepts($previewRequest);
        $unlistedAccepted = $this->accepts($unlistedRequest);

        // Assert
        $this->assertTrue($tailnetAccepted);
        $this->assertTrue($internalAccepted);
        $this->assertTrue($previewAccepted);
        $this->assertFalse($unlistedAccepted);
    }

    public function test_poisoned_host_is_rejected(): void
    {
        // Arrange
        $request = Request::create('https://evil.example.com/login');

        // Act
        $accepted = $this->accepts($request);

        // Assert
        $this->assertFalse($accepted);
    }

    public function test_poisoned_x_forwarded_host_is_rejected(): void
    {
        // Arrange
        $request = Request::create(self::CANONICAL.'/login');
        $request->headers->set('X-Forwarded-Host', 'evil.example.com');
        $request->setTrustedProxies(
            ['0.0.0.0/0', '2000::/3'],
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT
        );

        // Act
        $accepted = $this->accepts($request);

        // Assert
        $this->assertFalse($accepted);
    }

    public function test_forwarded_host_from_trusted_proxy_is_accepted(): void
    {
        // Arrange
        $request = Request::create('https://evil.example.com/login');
        $request->headers->set('X-Forwarded-Host', 'app.example.com');
        $request->setTrustedProxies(
            ['0.0.0.0/0', '2000::/3'],
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT
        );

        // Act
        $accepted = $this->accepts($request);

        // Assert
        $this->assertTrue($accepted);
    }

    public function test_forwarded_host_from_non_trusted_proxy_is_rejected_if_host_is_allowed(): void
    {
        // Arrange
        $request = Request::create('https://evil.example.com/login');
        $request->headers->set('X-Forwarded-Host', 'app.example.com');
        $request->setTrustedProxies(
            ['1.2.3.4/32'], // Not a trusted proxy
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT
        );

        // Act
        $accepted = $this->accepts($request);

        // Assert
        $this->assertFalse($accepted);
    }

    public function test_health_check_endpoint_bypasses_host_validation(): void
    {
        // Arrange
        $internalIpRequest = Request::create('https://0.0.0.0/health-check/up');
        $localhostRequest = Request::create('http://localhost/health-check/up');

        // Act
        $internalIpAccepted = $this->accepts($internalIpRequest);
        $localhostAccepted = $this->accepts($localhostRequest);

        // Assert
        $this->assertTrue($internalIpAccepted);
        $this->assertTrue($localhostAccepted);
    }

    public function test_health_check_endpoint_clears_state_before_other_middleware_reads_the_host(): void
    {
        // Arrange
        Request::setTrustedHosts(['^app\.example\.com$']);

        // Act
        $response = $this->get(self::CANONICAL.'/health-check/up', ['Host' => '0.0.0.0']);

        // Assert
        $response->assertSuccessful()
            ->assertExactJson(['success' => true]);
    }

    public function test_untrusted_host_renders_a_helpful_error(): void
    {
        // Arrange
        $handler = app(ExceptionHandler::class);
        $exception = new SuspiciousOperationException('Untrusted Host "evil.example.com".');
        $request = Request::create('https://evil.example.com/login');

        // Act
        $response = $handler->render($request, $exception);

        // Assert
        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('TRUSTED_HOSTS', (string) $response->getContent());
    }

    public function test_untrusted_host_returns_json_for_api_clients(): void
    {
        // Arrange
        $handler = app(ExceptionHandler::class);
        $exception = new SuspiciousOperationException('Untrusted Host "evil.example.com".');
        $request = Request::create('https://evil.example.com/api/v1/users');
        $request->headers->set('Accept', 'application/json');

        // Act
        $response = $handler->render($request, $exception);

        // Assert
        $this->assertSame(400, $response->getStatusCode());
        $this->assertJson((string) $response->getContent());
        $this->assertStringContainsString('TRUSTED_HOSTS', (string) $response->getContent());
    }
}
