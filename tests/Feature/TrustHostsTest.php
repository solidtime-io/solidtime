<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrustHostsTest extends TestCase
{
    private const CANONICAL = 'https://app.example.com';

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
        $this->middleware()->handle($request, fn (): string => 'passed');

        try {
            $request->getHost();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function test_canonical_host_is_accepted(): void
    {
        $this->assertTrue($this->accepts(Request::create(self::CANONICAL.'/login')));
    }

    public function test_subdomain_of_canonical_host_is_accepted(): void
    {
        $this->assertTrue($this->accepts(Request::create('https://team.app.example.com/login')));
    }

    public function test_declared_trusted_host_is_accepted(): void
    {
        config(['app.trusted_hosts' => ['box.tailnet.ts.net']]);

        $this->assertTrue($this->accepts(Request::create('https://box.tailnet.ts.net/login')));
    }

    public function test_wildcard_trusted_host_matches_subdomains_only(): void
    {
        config(['app.trusted_hosts' => ['*.example.net']]);

        $this->assertTrue($this->accepts(Request::create('https://foo.example.net/login')));
        $this->assertTrue($this->accepts(Request::create('https://a.b.example.net/login')));
        // The apex is not matched by the wildcard, and suffix-injection is rejected.
        $this->assertFalse($this->accepts(Request::create('https://example.net/login')));
        $this->assertFalse($this->accepts(Request::create('https://example.net.evil.com/login')));
    }

    public function test_multiple_trusted_hosts_are_all_accepted(): void
    {
        config(['app.trusted_hosts' => [
            'box.tailnet.ts.net',
            'solidtime.internal',
            '*.preview.example.com',
        ]]);

        $this->assertTrue($this->accepts(Request::create('https://box.tailnet.ts.net/login')));
        $this->assertTrue($this->accepts(Request::create('https://solidtime.internal/login')));
        $this->assertTrue($this->accepts(Request::create('https://pr-42.preview.example.com/login')));
        // A host that is not listed is still rejected.
        $this->assertFalse($this->accepts(Request::create('https://evil.example.com/login')));
    }

    public function test_poisoned_host_is_rejected(): void
    {
        $this->assertFalse($this->accepts(Request::create('https://evil.example.com/login')));
    }

    public function test_poisoned_x_forwarded_host_is_rejected(): void
    {
        $request = Request::create(self::CANONICAL.'/login');
        $request->headers->set('X-Forwarded-Host', 'evil.example.com');
        $request->setTrustedProxies(
            ['0.0.0.0/0', '2000::/3'],
            Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT
        );

        // getHost() now resolves to the poisoned X-Forwarded-Host value.
        $this->assertFalse($this->accepts($request));
    }

    public function test_health_check_endpoint_bypasses_host_validation(): void
    {
        // Probed on internal hosts/IPs; must not be rejected.
        $this->assertTrue($this->accepts(Request::create('https://0.0.0.0/health-check/up')));
        $this->assertTrue($this->accepts(Request::create('http://localhost/health-check/up')));
    }
}
