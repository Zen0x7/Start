<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\TotpUsageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $logins = LoginAttempt::where('user_id', $user->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'type' => 'login',
                'successful' => $a->successful,
                'email' => $a->email,
                'ip_address' => $a->ip_address,
                'user_agent' => $a->user_agent,
                'created_at' => $a->created_at,
            ]);

        $totp = TotpUsageLog::where('user_id', $user->id)
            ->with('device')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($l) => [
                'type' => 'totp',
                'successful' => true,
                'action' => $l->action,
                'device' => $l->device?->label ?? 'Unknown',
                'ip_address' => $l->ip_address,
                'user_agent' => $l->user_agent,
                'created_at' => $l->created_at,
            ]);

        $activity = collect([...$logins, ...$totp])
            ->sortByDesc('created_at')
            ->take(30)
            ->values();

        return response()->json(['activity' => $activity]);
    }
}
