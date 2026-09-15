<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Claim;
use App\Models\Item;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function myClaim(Request $request, Item $item): \Illuminate\Http\JsonResponse
    {
        $claim = $item->claims()->where('user_id', $request->user()->id)->latest()->first();

        return ApiResponse::ok($claim ? [
            'id' => $claim->id,
            'status' => $claim->status,
            'created_at' => $claim->created_at,
        ] : null);
    }

    public function store(Request $request, Item $item): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        if ($item->user_id === $user->id) {
            return ApiResponse::fail('Kamu tidak dapat mengklaim laporanmu sendiri.', 403);
        }

        if (! in_array($item->status, ['lost', 'found'])) {
            return ApiResponse::fail('Barang ini sudah tidak dapat diklaim.', 400);
        }

        if ($item->claims()->where('user_id', $user->id)->exists()) {
            return ApiResponse::fail('Kamu sudah mengajukan klaim untuk barang ini.', 400);
        }

        $submitted = $request->input('answers', []);
        $correctAnswers = $item->verification_answers ?: [];

        $answers = [];
        foreach (array_slice($submitted, 0, 10) as $i => $answer) {
            $qa = $correctAnswers[$i] ?? null;
            $answers[] = [
                'q' => (string) ($answer['q'] ?? ''),
                'user_answer' => (string) ($answer['user_answer'] ?? ''),
                'correct' => $qa
                    ? strcasecmp(trim((string) $qa['a']), trim((string) $answer['user_answer'])) === 0
                    : false,
            ];
        }

        $claim = $item->claims()->create([
            'user_id' => $user->id,
            'answers' => $answers ?: null,
            'status' => 'pending',
        ]);

        AppNotification::create([
            'user_id' => $item->user_id,
            'type' => 'claim_pending',
            'title' => 'Klaim baru masuk',
            'message' => "{$user->name} mengklaim '{$item->name}'.",
            'reference_id' => $item->id,
        ]);

        return ApiResponse::ok([
            'id' => $claim->id,
            'status' => $claim->status,
        ], 201);
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $status = $request->input('status');

        $claims = Claim::query()
            ->with('item.user:id,name', 'user:id,name')
            ->when($status && in_array($status, ['pending', 'approved', 'rejected']), fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return ApiResponse::ok($claims->map(function (Claim $claim) {
            return [
                'id' => $claim->id,
                'claimant_name' => $claim->user?->name,
                'item_name' => $claim->item?->name,
                'owner_name' => $claim->item?->user?->name,
                'created_at' => $claim->created_at,
                'status' => $claim->status,
                'answers' => $claim->answers ?: [],
            ];
        }));
    }

    public function approve(Request $request, Claim $claim): \Illuminate\Http\JsonResponse
    {
        if ($claim->status !== 'pending') {
            return ApiResponse::fail('Klaim ini sudah ditinjau.', 400);
        }

        $admin = $request->user();

        $claim->update([
            'status' => 'approved',
            'reviewer_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $claim->item->update(['status' => 'claimed']);

        foreach ($claim->item->claims()->where('id', '!=', $claim->id)->where('status', 'pending')->get() as $other) {
            $other->update(['status' => 'rejected', 'reviewer_id' => $admin->id, 'reviewed_at' => now()]);
            AppNotification::create([
                'user_id' => $other->user_id,
                'type' => 'claim_rejected',
                'title' => 'Klaim ditolak',
                'message' => "Klaimmu untuk '{$claim->item->name}' ditolak karena barang sudah diklaim orang lain.",
                'reference_id' => $claim->item->id,
            ]);
        }

        AppNotification::create([
            'user_id' => $claim->user_id,
            'type' => 'claim_approved',
            'title' => 'Klaim disetujui!',
            'message' => "Klaimmu untuk '{$claim->item->name}' disetujui.",
            'reference_id' => $claim->item->id,
        ]);

        AppNotification::create([
            'user_id' => $claim->item->user_id,
            'type' => 'claim_approved',
            'title' => 'Klaim disetujui',
            'message' => "Barangmu '{$claim->item->name}' akan diserahkan kepada {$claim->user->name}.",
            'reference_id' => $claim->item->id,
        ]);

        return ApiResponse::ok();
    }

    public function reject(Request $request, Claim $claim): \Illuminate\Http\JsonResponse
    {
        if ($claim->status !== 'pending') {
            return ApiResponse::fail('Klaim ini sudah ditinjau.', 400);
        }

        $claim->update([
            'status' => 'rejected',
            'reviewer_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AppNotification::create([
            'user_id' => $claim->user_id,
            'type' => 'claim_rejected',
            'title' => 'Klaim ditolak',
            'message' => "Klaimmu untuk '{$claim->item->name}' ditolak.",
            'reference_id' => $claim->item->id,
        ]);

        return ApiResponse::ok();
    }
}