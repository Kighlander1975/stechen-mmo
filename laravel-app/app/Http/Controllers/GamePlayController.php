<?php

namespace App\Http\Controllers;

use App\Services\GameRooms\GameRoomPlayStateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RuntimeException;

class GamePlayController extends Controller
{
    public function __invoke(
        Request $request,
        string $publicCode,
        GameRoomPlayStateService $playStateService,
    ): View {
        try {
            $initialState = $playStateService->build($request->user(), $publicCode);
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('game.play', [
            'publicCode' => $publicCode,
            'initialState' => $initialState,
            'stateUrl' => route('game.play.state', ['publicCode' => $publicCode]),
            'finishUrl' => route('game.play.finish', ['publicCode' => $publicCode]),
        ]);
    }
}
