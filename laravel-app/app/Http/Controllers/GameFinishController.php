<?php

namespace App\Http\Controllers;

use App\Services\GameRooms\GameRoomFinishService;
use App\Services\GameRooms\GameRoomPlayStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GameFinishController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicCode,
        GameRoomFinishService $finishService,
        GameRoomPlayStateService $playStateService,
    ): JsonResponse {
        try {
            $finishService->finishForUser($request->user(), $publicCode);

            return response()->json(
                $playStateService->build($request->user(), $publicCode),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
