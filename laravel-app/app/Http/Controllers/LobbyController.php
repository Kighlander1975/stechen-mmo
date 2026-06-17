<?php

namespace App\Http\Controllers;

use App\Services\Lobby\LobbyRoomQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function __invoke(Request $request, LobbyRoomQueryService $lobbyRoomQueryService): View
    {
        $filters = [
            'status' => $request->query('status'),
            'start_mode' => $request->query('start_mode'),
            'buy_in' => $request->query('buy_in'),
            'players' => $request->query('players'),
        ];

        return view('lobby.index', [
            'gameRooms' => $lobbyRoomQueryService->getFilteredRooms($filters),
            'filters' => $filters,
        ]);
    }
}
