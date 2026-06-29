<?php

namespace App\Http\Controllers;

use App\Services\GameRooms\GameRoomPlayStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GamePlayStateController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicCode,
        GameRoomPlayStateService $playStateService,
    ): JsonResponse {
        try {
            return response()->json(
                $playStateService->build($request->user(), $publicCode),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 403);
        }
    }
}
