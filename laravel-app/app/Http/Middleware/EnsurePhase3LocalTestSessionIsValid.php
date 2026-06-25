<?php

namespace App\Http\Middleware;

use App\Services\Phase3\Phase3LocalTestHarnessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhase3LocalTestSessionIsValid
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $phase3LocalTestHarness = app(Phase3LocalTestHarnessService::class);

        if (! $phase3LocalTestHarness->isPhase3TestUser($user)) {
            return $next($request);
        }

        if ($phase3LocalTestHarness->isEnabled()) {
            return $next($request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('warning', 'Der lokale Phase-3-Testmodus wurde deaktiviert. Bitte melde dich erneut an.');
    }
}
