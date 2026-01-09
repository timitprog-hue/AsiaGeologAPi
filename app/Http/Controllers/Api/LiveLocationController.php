<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveLocation;
use Illuminate\Http\Request;

class LiveLocationController extends Controller
{
    public function upsert(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'latitude'    => ['required','numeric'],
            'longitude'   => ['required','numeric'],
            'accuracy_m'  => ['nullable','numeric'],
            'captured_at' => ['nullable','date'],
        ]);

        $row = LiveLocation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy_m' => $data['accuracy_m'] ?? null,
                'captured_at' => $data['captured_at'] ?? now(),
            ]
        );

        return response()->json(['data' => $row]);
    }

    public function index(Request $request)
    {
        $q = LiveLocation::query()->with('user:id,name,email,role');

        if ($request->filled('user_id')) {
            $q->where('user_id', $request->user_id);
        }

        $rows = $q->get()->map(function ($r) {
            $r->is_online = $r->updated_at->gt(now()->subMinutes(2));
            return $r;
        });

        return response()->json(['data' => $rows]);
    }
}

