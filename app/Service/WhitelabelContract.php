<?php

declare(strict_types=1);

namespace App\Service;

/**
 * This class is a contract for whitelabeling the instance: the name and the logos users see in the app, in emails
 * and in the admin panel. It applies to the whole instance, not to a single organization.
 * The default is the solidtime branding; the whitelabel extension for premium self-hosters rebinds this contract.
 *
 * The methods are only called where the values are rendered (full page loads, auth pages, emails, admin panel),
 * not on API requests or Inertia visits. Implementations should still avoid expensive lookups on every call,
 * the instance is scoped to the request, so it can keep loaded values for the rest of the request.
 */
class WhitelabelContract
{
    /**
     * The name of the application, e.g. used in the page title and in emails.
     */
    public function getName(): string
    {
        return 'solidtime';
    }

    /**
     * Absolute URL of the logo (wordmark) for light backgrounds as PNG or JPEG, since many email clients do not display SVG.
     * Null means the default solidtime logo is used.
     */
    public function getLogoUrl(): ?string
    {
        return null;
    }

    /**
     * Absolute URL of an SVG version of the logo for light backgrounds.
     * Web pages prefer it, emails offer it to the email clients that support it (srcset) and fall back to getLogoUrl().
     * Null means there is no SVG version.
     */
    public function getLogoSvgUrl(): ?string
    {
        return null;
    }

    /**
     * Absolute URL of the logo (wordmark) for dark backgrounds, it is only used on web pages, so it can be an SVG.
     * Null means the logo for light backgrounds is used, or the default solidtime logo if there is none.
     */
    public function getDarkLogoUrl(): ?string
    {
        return null;
    }

    /**
     * Absolute URL of the square icon used as favicon.
     * Null means the default solidtime favicons are used.
     */
    public function getFaviconUrl(): ?string
    {
        return null;
    }

    /**
     * Path of the favicon on the public disk, so /favicon.ico can serve the file itself.
     * Null means the favicon is not on the public disk (e.g. an external URL) or there is no whitelabel favicon.
     */
    public function getFaviconPath(): ?string
    {
        return null;
    }

    /**
     * Hex color (e.g. #000000) of the browser UI on mobile (theme-color) and of the installed app (PWA manifest).
     * It is not used in the app itself.
     */
    public function getThemeColor(): string
    {
        return '#000000';
    }

    /**
     * The logo URL for web pages: the SVG version if there is one, the logo otherwise.
     */
    public function getWebLogoUrl(): ?string
    {
        return $this->getLogoSvgUrl() ?? $this->getLogoUrl();
    }
}
