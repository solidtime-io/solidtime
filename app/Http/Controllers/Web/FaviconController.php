<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Service\WhitelabelContract;
use finfo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Browsers and many bots request /favicon.ico on their own (e.g. for bookmarks, link previews or pages that are not HTML).
 * The favicon is served directly, since not every client follows redirects.
 * The route has no middleware group (no session), so it is cheap, especially with Octane.
 */
class FaviconController extends Controller
{
    private const string CACHE_CONTROL = 'public, max-age=86400';

    public function show(WhitelabelContract $whitelabel): BinaryFileResponse|RedirectResponse|Response
    {
        $path = $whitelabel->getFaviconPath();
        if ($path !== null) {
            // Reads the file from the public disk (e.g. S3) on every request, browsers cache it for a day
            $content = $this->readFromPublicDisk($path);
            if ($content !== null) {
                $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($content);

                return response($content, 200, [
                    'Content-Type' => is_string($mimeType) ? $mimeType : 'application/octet-stream',
                    'Cache-Control' => self::CACHE_CONTROL,
                ]);
            }
        } else {
            // A favicon on an external URL (e.g. a CDN) is not downloaded on every request
            $url = $whitelabel->getFaviconUrl();
            if ($url !== null) {
                return redirect()->to($url)->header('Cache-Control', self::CACHE_CONTROL);
            }
        }

        // The solidtime favicon, also when the whitelabel favicon file is missing
        return response()->file(public_path('favicons/favicon.ico'), [
            'Content-Type' => 'image/x-icon',
            'Cache-Control' => self::CACHE_CONTROL,
        ]);
    }

    private function readFromPublicDisk(string $path): ?string
    {
        try {
            $content = Storage::disk(config('filesystems.public'))->get($path);
        } catch (Throwable) {
            return null;
        }

        return is_string($content) && $content !== '' ? $content : null;
    }
}
