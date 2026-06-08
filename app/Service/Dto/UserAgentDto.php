<?php

declare(strict_types=1);

namespace App\Service\Dto;

use Closure;
use Detection\MobileDetect;

/**
 * @copyright Originally created by Jens Segers: https://github.com/jenssegers/agent
 */
class UserAgentDto extends MobileDetect
{
    /**
     * List of additional operating systems.
     *
     * @var array<string, string>
     */
    protected static array $additionalOperatingSystems = [
        'Windows' => 'Windows',
        'Windows NT' => 'Windows NT',
        'OS X' => 'Mac OS X',
        'Debian' => 'Debian',
        'Ubuntu' => 'Ubuntu',
        'Macintosh' => 'PPC',
        'OpenBSD' => 'OpenBSD',
        'Linux' => 'Linux',
        'ChromeOS' => 'CrOS',
    ];

    /**
     * List of additional browsers.
     *
     * @var array<string, string>
     */
    protected static array $additionalBrowsers = [
        'Opera Mini' => 'Opera Mini',
        'Opera' => 'Opera|OPR',
        'Edge' => 'Edge|Edg',
        'Coc Coc' => 'coc_coc_browser',
        'UCBrowser' => 'UCBrowser',
        'Vivaldi' => 'Vivaldi',
        'Chrome' => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari' => 'Safari',
        'IE' => 'MSIE|IEMobile|MSIEMobile|Trident/[.0-9]+',
        'Netscape' => 'Netscape',
        'Mozilla' => 'Mozilla',
        'WeChat' => 'MicroMessenger',
    ];

    /**
     * Key value store for resolved strings.
     *
     * @var array<string, mixed>
     */
    protected array $store = [];

    /**
     * Get the platform name from the User Agent.
     */
    public function platform(): ?string
    {
        return $this->retrieveUsingCacheOrResolve('platform', function () {
            return $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(MobileDetect::getOperatingSystems(), static::$additionalOperatingSystems)
            );
        });
    }

    /**
     * Get the browser name from the User Agent.
     */
    public function browser(): ?string
    {
        return $this->retrieveUsingCacheOrResolve('browser', function (): ?string {
            return $this->findDetectionRulesAgainstUserAgent(
                $this->mergeRules(static::$additionalBrowsers, MobileDetect::getBrowsers())
            );
        });
    }

    /**
     * Determine if the device is a desktop computer.
     */
    public function isDesktop(): bool
    {
        return $this->retrieveUsingCacheOrResolve('desktop', function (): bool {
            // Check specifically for cloudfront headers if the useragent === 'Amazon CloudFront'
            if (
                $this->getUserAgent() === static::$cloudFrontUA
                && $this->getHttpHeader('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER') === 'true'
            ) {
                return true;
            }

            return ! $this->isMobile() && ! $this->isTablet();
        });
    }

    /**
     * Match a detection rule and return the matched key.
     *
     * @param  array<string, string|list<string>>  $rules
     */
    protected function findDetectionRulesAgainstUserAgent(array $rules): ?string
    {
        $userAgent = $this->getUserAgent();

        foreach ($rules as $key => $regex) {
            if (is_array($regex)) {
                $regex = implode('|', $regex);
            }

            if (empty($regex)) {
                continue;
            }

            if ($this->match($regex, $userAgent)) {
                if ($key !== '') {
                    return $key;
                }

                $match = reset($this->matchesArray);

                return is_string($match) ? $match : null;
            }
        }

        return null;
    }

    /**
     * Retrieve from the given key from the cache or resolve the value.
     *
     * @template TReturn of string|bool|null
     *
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    protected function retrieveUsingCacheOrResolve(string $key, Closure $callback): string|bool|null
    {
        $cacheKey = $this->createCacheKey($key);

        if (! is_null($cacheItem = $this->store[$cacheKey] ?? null)) {
            return $cacheItem;
        }

        return tap(call_user_func($callback), function ($result) use ($cacheKey): void {
            $this->store[$cacheKey] = $result;
        });
    }

    /**
     * Merge multiple rules into one array.
     *
     * @param  array<string, string|list<string>>  ...$all
     * @return array<string, string>
     */
    protected function mergeRules(array ...$all): array
    {
        $merged = [];

        foreach ($all as $rules) {
            foreach ($rules as $key => $value) {
                $value = is_array($value) ? implode('|', $value) : $value;

                if (empty($merged[$key])) {
                    $merged[$key] = $value;
                } else {
                    $merged[$key] .= '|'.$value;
                }
            }
        }

        return $merged;
    }
}
