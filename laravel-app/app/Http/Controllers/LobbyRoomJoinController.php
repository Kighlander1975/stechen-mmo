<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Services\GameRooms\GameRoomJoinService;
use App\Services\Lobby\LobbyRoomBrowserPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LobbyRoomJoinController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicCode,
        GameRoomJoinService $joinService,
        LobbyRoomBrowserPayloadService $payloadService,
    ): JsonResponse {
        $room = GameRoom::query()
            ->where('public_code', $publicCode)
            ->firstOrFail();

        try {
            $joinService->join($request->user(), $room);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'lobby' => $payloadService->build(
                    $this->filtersFromRequest($request),
                    $publicCode,
                    $request->user(),
                ),
            ], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Du bist dem Raum beigetreten.',
            'lobby' => $payloadService->build(
                $this->filtersFromRequest($request),
                $publicCode,
                $request->user(),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'status' => $request->query('status'),
            'start_mode' => $request->query('start_mode'),
            'buy_in' => $request->query('buy_in'),
            'players' => $request->query('players'),
            'only_test' => $request->query('only_test'),
        ];
    }
}
