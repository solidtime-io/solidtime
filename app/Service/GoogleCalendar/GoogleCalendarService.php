<?php

declare(strict_types=1);

namespace App\Service\GoogleCalendar;

use App\Exceptions\Api\GoogleCalendarConnectionBrokenException;
use App\Models\GoogleCalendarConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleCalendarService
{
    private const string TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const string REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    private const string EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    /**
     * Fetch the events of the connected Google account's primary calendar in the given time range.
     *
     * @return list<array{id: string, summary: string, start: string, end: string, all_day: bool, html_link: string|null}>
     *
     * @throws GoogleCalendarConnectionBrokenException
     */
    public function getEvents(GoogleCalendarConnection $connection, Carbon $start, Carbon $end): array
    {
        $accessToken = $this->getValidAccessToken($connection);

        $events = [];
        $pageToken = null;
        do {
            $query = [
                'timeMin' => $start->toRfc3339String(),
                'timeMax' => $end->toRfc3339String(),
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 250,
            ];
            if ($pageToken !== null) {
                $query['pageToken'] = $pageToken;
            }

            $response = Http::withToken($accessToken)->get(self::EVENTS_URL, $query);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new GoogleCalendarConnectionBrokenException;
            }
            if ($response->failed()) {
                Log::warning('Google Calendar events request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuntimeException('Google Calendar events request failed');
            }

            foreach ($response->json('items', []) as $item) {
                $event = $this->mapEvent($item);
                if ($event !== null) {
                    $events[] = $event;
                }
            }

            $pageToken = $response->json('nextPageToken');
        } while ($pageToken !== null);

        return $events;
    }

    /**
     * Best-effort revocation of the Google grant, used on disconnect.
     */
    public function revoke(GoogleCalendarConnection $connection): void
    {
        try {
            Http::asForm()->post(self::REVOKE_URL, [
                'token' => $connection->refresh_token,
            ]);
        } catch (\Throwable $exception) {
            Log::debug('Google Calendar token revocation failed', ['message' => $exception->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{id: string, summary: string, start: string, end: string, all_day: bool, html_link: string|null}|null
     */
    private function mapEvent(array $item): ?array
    {
        if (($item['status'] ?? null) === 'cancelled') {
            return null;
        }

        $start = $item['start']['dateTime'] ?? $item['start']['date'] ?? null;
        $end = $item['end']['dateTime'] ?? $item['end']['date'] ?? null;
        if (! is_string($start) || ! is_string($end) || ! isset($item['id'])) {
            return null;
        }

        // Skip events the user has declined
        foreach ($item['attendees'] ?? [] as $attendee) {
            if (($attendee['self'] ?? false) === true && ($attendee['responseStatus'] ?? null) === 'declined') {
                return null;
            }
        }

        return [
            'id' => (string) $item['id'],
            'summary' => isset($item['summary']) ? (string) $item['summary'] : '',
            'start' => $start,
            'end' => $end,
            'all_day' => ! isset($item['start']['dateTime']),
            'html_link' => isset($item['htmlLink']) ? (string) $item['htmlLink'] : null,
        ];
    }

    /**
     * @throws GoogleCalendarConnectionBrokenException
     */
    private function getValidAccessToken(GoogleCalendarConnection $connection): string
    {
        if ($connection->access_token !== null
            && $connection->access_token_expires_at !== null
            && $connection->access_token_expires_at->isAfter(Carbon::now()->addMinute())) {
            return $connection->access_token;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::info('Google Calendar access token refresh failed', [
                'status' => $response->status(),
                'error' => $response->json('error'),
            ]);

            throw new GoogleCalendarConnectionBrokenException;
        }

        $accessToken = $response->json('access_token');
        $expiresIn = $response->json('expires_in');
        if (! is_string($accessToken) || ! is_int($expiresIn)) {
            throw new GoogleCalendarConnectionBrokenException;
        }

        $connection->access_token = $accessToken;
        $connection->access_token_expires_at = Carbon::now()->addSeconds($expiresIn);
        $connection->save();

        return $accessToken;
    }
}
