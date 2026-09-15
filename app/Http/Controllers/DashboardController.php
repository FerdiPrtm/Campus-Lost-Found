<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\Item;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $mine = Item::where('user_id', $user->id)->get();

        $stats = [
            'lost' => $mine->where('status', 'lost')->count(),
            'found' => $mine->where('status', 'found')->count(),
            'claimed' => $mine->where('status', 'claimed')->count(),
            'returned' => $mine->where('status', 'returned')->count(),
        ];

        return ApiResponse::ok([
            'stats' => $stats,
            'reports' => $mine->filter(fn (Item $i) => in_array($i->status, ['lost', 'found', 'claimed', 'verified', 'returned']))
                ->sortByDesc('created_at')
                ->take(50)
                ->values()
                ->map(fn (Item $i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'image' => $i->image,
                    'location' => $i->location,
                    'status' => $i->status,
                    'created_at' => $i->created_at,
                ]),
            'recent_notifications' => $user->notifications()
                ->orderByDesc('created_at')
                ->take(5)
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'message' => $n->message,
                    'reference_id' => $n->reference_id,
                    'created_at' => $n->created_at,
                ]),
            'claims' => $user->claims()
                ->latest()
                ->take(50)
                ->get()
                ->map(fn (Claim $c) => [
                    'id' => $c->id,
                    'item_id' => $c->item_id,
                    'item_name' => $c->item?->name,
                    'status' => $c->status,
                    'created_at' => $c->created_at,
                ]),
        ]);
    }
}