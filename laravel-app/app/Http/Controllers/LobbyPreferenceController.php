<?php

namespace App\Http\Controllers;

use App\Services\Lobby\LobbyFilterPreferenceService;
use App\Services\Lobby\LobbyRoomBrowserPayloadService;
use App\Services\Lobby\LobbyRoomQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LobbyPreferenceController extends Controller
{
    public function __invoke(
        Request $request,
        LobbyFilterPreferenceService $preferenceService,
        LobbyRoomBrowserPayloadService $payloadService,
        LobbyRoomQueryService $roomQueryService,
    ): JsonResponse {
        $validated = $request->validate([
            'status' => ['present', 'nullable', 'string', Rule::in($roomQueryService->allowedStatuses())],
            'start_mode' => ['present', 'nullable', 'string', Rule::in($roomQueryService->allowedStartModes())],
            'buy_in' => ['present', 'nullable', 'string', Rule::in($roomQueryService->allowedBuyInCategories())],
            'players' => ['present', 'nullable', 'string', Rule::in($roomQueryService->allowedPlayerCategories())],
            'only_test' => ['present', 'boolean'],
            'room' => ['nullable', 'string', 'max:64'],
        ]);

        $filters = $preferenceService->save($request->user(), $validated);

        return response()->json([
            'status' => 'ok',
            'message' => 'Lobbyfilter wurden gespeichert.',
            'filters' => $filters,
            'lobby' => $payloadService->build(
                $filters,
                $validated['room'] ?? null,
                $request->user(),
            ),
        ]);
    }
}
