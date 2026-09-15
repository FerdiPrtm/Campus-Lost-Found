<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unread = AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return ApiResponse::ok([
            'items' => $notifications->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'reference_id' => $n->reference_id,
                'is_read' => $n->is_read ? 1 : 0,
                'created_at' => $n->created_at,
            ]),
            'unread' => $unread,
        ]);
    }

    public function read(Request $request, AppNotification $notification): \Illuminate\Http\JsonResponse
    {
        if ($notification->user_id === $request->user()->id && ! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return ApiResponse::ok();
    }

    public function readAll(Request $request): \Illuminate\Http\JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return ApiResponse::ok();
    }
}