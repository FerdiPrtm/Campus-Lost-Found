<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Item;
use App\Models\Message;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function conversations(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $rows = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->with(['item:id,name,image,type', 'sender:id,name', 'receiver:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $conversations = [];
        foreach ($rows as $m) {
            $other = $m->sender_id === $user->id ? $m->receiver : $m->sender;
            $key = $m->item_id . '-' . $other->id;
            if (isset($conversations[$key])) {
                $conversations[$key]['last_message'] = $m->body;
                $conversations[$key]['last_at'] = $m->created_at;
                if ($m->receiver_id === $user->id && ! $m->read_at) {
                    $conversations[$key]['unread']++;
                }
                continue;
            }

            $item = $m->item;
            if (! $item) {
                continue;
            }

            $conversations[$key] = [
                'id' => $key,
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_image' => $item->image,
                'item_type' => $item->type,
                'user' => ['id' => $other->id, 'name' => $other->name],
                'last_message' => $m->body,
                'last_at' => $m->created_at,
                'unread' => $m->receiver_id === $user->id && ! $m->read_at ? 1 : 0,
            ];
        }

        return ApiResponse::ok([
            'conversations' => array_values($conversations),
            'unread' => Message::where('receiver_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }

    public function thread(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $itemId = (int) $request->query('item_id');
        $otherId = (int) $request->query('with');

        $item = Item::find($itemId);
        if (! $item) {
            return ApiResponse::fail('Tidak ditemukan.', 404);
        }

        $isOwnerSide = in_array($item->user_id, [$user->id, $otherId], true);
        $hasHistory = Message::where('item_id', $itemId)
            ->where(function ($q) use ($user, $otherId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $otherId)
                    ->orWhere(function ($w) use ($user, $otherId) {
                        $w->where('sender_id', $otherId)->where('receiver_id', $user->id);
                    });
            })
            ->exists();

        if ($isOwnerSide && $item->moderation_status !== 'approved' && ! $hasHistory) {
            return ApiResponse::fail('Tidak ditemukan.', 404);
        }

        if (! $isOwnerSide && ! $hasHistory) {
            return ApiResponse::fail('Tidak ditemukan.', 404);
        }

        Message::where('item_id', $itemId)
            ->where('sender_id', $otherId)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::where('item_id', $itemId)
            ->where(function ($q) use ($user, $otherId) {
                $q->where(fn ($w) => $w->where('sender_id', $user->id)->where('receiver_id', $otherId))
                    ->orWhere(fn ($w) => $w->where('sender_id', $otherId)->where('receiver_id', $user->id));
            })
            ->orderBy('created_at')
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'body' => $m->body,
                'created_at' => $m->created_at,
            ]);

        $other = \App\Models\User::find($otherId);

        return ApiResponse::ok([
            'item_id' => $itemId,
            'item_name' => $item->name,
            'with' => $otherId,
            'other' => $other ? ['id' => $other->id, 'name' => $other->name] : null,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_id' => ['required', 'integer'],
            'receiver_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::fail($validator->errors()->first(), 422);
        }

        $user = $request->user();
        $item = Item::find($request->integer('item_id'));

        if (! $item || $item->moderation_status !== 'approved') {
            return ApiResponse::fail('Item tidak ditemukan atau belum disetujui.', 404);
        }

        $receiverId = $request->integer('receiver_id');
        if ($receiverId === $user->id) {
            return ApiResponse::fail('Tidak bisa chat dengan diri sendiri.', 422);
        }

        if (! in_array($item->user_id, [$user->id, $receiverId], true)) {
            return ApiResponse::fail('Chat hanya antara pelapor dan penanggap.', 422);
        }

        $message = Message::create([
            'item_id' => $item->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'body' => trim($request->string('body')),
        ]);

        if ($receiverId !== $item->user_id) {
            AppNotification::create([
                'user_id' => $receiverId,
                'type' => 'new_message',
                'title' => 'Pesan baru',
                'message' => "Pesan baru soal '{$item->name}' dari {$user->name}.",
                'reference_id' => $item->id,
                'is_read' => false,
            ]);
        }

        return ApiResponse::ok([
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'body' => $message->body,
            'created_at' => $message->created_at,
        ], 201);
    }

    public function unread(Request $request): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::ok(['unread' => Message::where('receiver_id', $request->user()->id)->whereNull('read_at')->count()]);
    }
}