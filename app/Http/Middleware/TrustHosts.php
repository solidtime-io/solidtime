<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as BaseTrustHosts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Rejects requests whose Host is not trusted, preventing Host-header poisoning of
 * generated URLs (password reset, SSO callback, invitations). Trusted = the
 * APP_URL host and its subdomains, plus TRUSTED_HOSTS (for multi-host access such
 * as a Tailscale name). Health-check endpoints are exempt (probed by IP).
 */
class TrustHosts extends BaseTrustHosts
{
    /**
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        /** @var array<int, string> $configured */
        $configured = config('app.trusted_hosts', []);

        $extra = array_map(function (string $host): string {
            $host = trim($host);

            // "*.example.com" matches any subdomain, not the apex.
            if (str_starts_with($host, '*.')) {
                return '^.+\.'.preg_quote(substr($host, 2), '#').'$';
            }

            return '^'.preg_quote($host, '#').'$';
        }, $configured);

        return array_merge([$this->allSubdomainsOfApplicationUrl()], $extra);
    }

    /**
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, $next): Response
    {
        // Exempt health checks (probed by IP). Also reset the trusted hosts,
        // since Octane leaks the static state across requests.
        if ($request->is('health-check/*')) {
            Request::setTrustedHosts([]);

            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
