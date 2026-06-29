<?php

namespace App\Http\Controllers;

use App\Services\Lobby\LobbyRoomBrowserPayloadService;
use App\Services\Lobby\LobbyRoomQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LobbyRoomsController extends Controller
{
    public function __invoke(
        Request $request,
        LobbyRoomBrowserPayloadService $payloadService,
        LobbyRoomQueryService $roomQueryService,
    ): JsonResponse {
        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in($roomQueryService->allowedStatuses())],
            'start_mode' => ['nullable', 'string', Rule::in($roomQueryService->allowedStartModes())],
            'buy_in' => ['nullable', 'string', Rule::in($payloadService->allowedBuyInCategories())],
            'players' => ['nullable', 'string', Rule::in($payloadService->allowedPlayerCategories())],
            'only_test' => ['nullable', 'boolean'],
            'room' => ['nullable', 'string', 'max:64'],
        ]);

        $payload = $payloadService->build(
            [
                'status' => $validated['status'] ?? null,
                'start_mode' => $validated['start_mode'] ?? null,
                'buy_in' => $validated['buy_in'] ?? null,
                'players' => $validated['players'] ?? null,
                'only_test' => $validated['only_test'] ?? false,
            ],
            $validated['room'] ?? null,
            $request->user(),
        );

        return response()->json($payload);
    }
}
