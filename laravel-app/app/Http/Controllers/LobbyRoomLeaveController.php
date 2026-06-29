<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Services\GameRooms\GameRoomLeaveService;
use App\Services\Lobby\LobbyRoomBrowserPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LobbyRoomLeaveController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicCode,
        GameRoomLeaveService $leaveService,
        LobbyRoomBrowserPayloadService $payloadService,
    ): JsonResponse {
        $room = GameRoom::query()
            ->where('public_code', $publicCode)
            ->firstOrFail();

        try {
            $left = $leaveService->leave($request->user(), $room);
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
            'message' => $left
                ? 'Du hast den Raum verlassen.'
                : 'Der Raum konnte nicht verlassen werden.',
            'lobby' => $payloadService->build(
                $this->filtersFromRequest($request),
                $publicCode,
                $request->user(),
            ),
        ], $left ? 200 : 422);
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
