<?php

namespace App\Http\Controllers;

use App\Models\GameRoom;
use App\Services\Lobby\LobbyRoomBrowserPayloadService;
use App\Services\Lobby\LobbyRoomQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LobbyController extends Controller
{
    public function __invoke(
        Request $request,
        LobbyRoomQueryService $lobbyRoomQueryService,
        LobbyRoomBrowserPayloadService $lobbyRoomBrowserPayloadService,
    ): View {
        $filters = [
            'status' => $request->query('status'),
            'start_mode' => $request->query('start_mode'),
            'buy_in' => $request->query('buy_in'),
            'players' => $request->query('players'),
            'only_test' => $request->query('only_test'),
        ];

        $selectedRoom = null;
        $selectedRoomCode = $request->string('room')->trim()->toString();

        if ($selectedRoomCode !== '') {
            $selectedRoom = GameRoom::query()
                ->withCount('activePlayers')
                ->where('public_code', $selectedRoomCode)
                ->whereIn('status', [
                    GameRoom::STATUS_OPEN,
                    GameRoom::STATUS_FULL,
                    GameRoom::STATUS_STARTING,
                    GameRoom::STATUS_RUNNING,
                    GameRoom::STATUS_FINISHED,
                ])
                ->first();
        }

        return view('lobby.index', [
            'gameRooms' => $lobbyRoomQueryService->getFilteredRooms($filters, $request->user()),
            'filters' => $filters,
            'selectedRoom' => $selectedRoom,
            'lobbyRoomBrowserProps' => $lobbyRoomBrowserPayloadService->build(
                $filters,
                $selectedRoomCode !== '' ? $selectedRoomCode : null,
                $request->user(),
            ),
        ]);
    }
}
