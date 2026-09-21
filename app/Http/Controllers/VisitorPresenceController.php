<?php

namespace App\Http\Controllers;

use App\Models\VisitorPresence;
use Illuminate\Http\Request;

class VisitorPresenceController extends Controller
{
    public function heartbeat(Request $request)
    {
        $now = now();
        $activeSince = $now->copy()->subMinutes(3);
        $visitorKey = hash('sha256', $request->session()->getId());

        VisitorPresence::updateOrCreate(
            ['visitor_key' => $visitorKey],
            ['last_seen_at' => $now],
        );
        VisitorPresence::where('last_seen_at', '<', $activeSince)->delete();

        return response()->json([
            'online' => VisitorPresence::where('last_seen_at', '>=', $activeSince)->count(),
        ]);
    }
}
