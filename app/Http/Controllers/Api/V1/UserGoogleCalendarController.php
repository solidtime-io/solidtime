<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\GoogleCalendarConnectionBrokenException;
use App\Exceptions\Api\GoogleCalendarNotConnectedException;
use App\Http\Requests\V1\GoogleCalendar\GoogleCalendarEventsRequest;
use App\Models\GoogleCalendarConnection;
use App\Service\GoogleCalendar\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserGoogleCalendarController extends Controller
{
    /**
     * Status of the Google Calendar connection of the currently authenticated user
     *
     * This endpoint is independent of the organization.
     *
     * @operationId getMyGoogleCalendarConnection
     */
    public function show(): JsonResponse
    {
        $user = $this->user();

        /** @var GoogleCalendarConnection|null $connection */
        $connection = GoogleCalendarConnection::query()
            ->where('user_id', '=', $user->getKey())
            ->first();

        return response()->json([
            'data' => [
                'available' => config('services.google.client_id') !== null,
                'connected' => $connection !== null,
                'google_email' => $connection?->google_email,
            ],
        ]);
    }

    /**
     * Google Calendar events of the currently authenticated user in the given time range
     *
     * This endpoint is independent of the organization.
     *
     * @operationId getMyGoogleCalendarEvents
     *
     * @throws GoogleCalendarNotConnectedException|GoogleCalendarConnectionBrokenException|ValidationException
     */
    public function events(GoogleCalendarEventsRequest $request, GoogleCalendarService $googleCalendarService): JsonResponse
    {
        $user = $this->user();

        /** @var GoogleCalendarConnection|null $connection */
        $connection = GoogleCalendarConnection::query()
            ->where('user_id', '=', $user->getKey())
            ->first();

        if ($connection === null) {
            throw new GoogleCalendarNotConnectedException;
        }

        $start = $request->getStart();
        $end = $request->getEnd();
        if ($start->diffInDays($end) > 62) {
            throw ValidationException::withMessages([
                'end' => [__('The requested time range is too long.')],
            ]);
        }

        return response()->json([
            'data' => $googleCalendarService->getEvents($connection, $start, $end),
        ]);
    }
}
