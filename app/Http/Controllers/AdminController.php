<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function stats(): \Illuminate\Http\JsonResponse
    {
        $approved = Item::where('moderation_status', 'approved');

        $total = (clone $approved)->count();
        $returned = (clone $approved)->where('status', 'returned')->count();

        $byMonth = (clone $approved)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, type, COUNT(*) as n')
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'type' => $row->type, 'n' => (int) $row->n]);

        $byCategory = (clone $approved)
            ->where('type', 'lost')
            ->selectRaw('category, COUNT(*) as n')
            ->groupBy('category')
            ->orderByDesc('n')
            ->get()
            ->map(fn ($row) => ['category' => $row->category, 'n' => (int) $row->n]);

        $byLocation = (clone $approved)
            ->selectRaw('location, COUNT(*) as n')
            ->groupBy('location')
            ->orderByDesc('n')
            ->get()
            ->map(fn ($row) => ['location' => $row->location, 'n' => (int) $row->n]);

        $recent = Item::query()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'reporter_name' => $item->user?->name,
                'moderation_status' => $item->moderation_status,
                'created_at' => $item->created_at,
            ]);

        return ApiResponse::ok([
            'stats' => [
                'total_reports' => $total,
                'lost_items' => (clone $approved)->where('type', 'lost')->count(),
                'found_items' => (clone $approved)->where('type', 'found')->count(),
                'returned_items' => $returned,
                'pending_reports' => Item::where('moderation_status', 'pending')->count(),
                'total_users' => User::count(),
            ],
            'recent_reports' => $recent,
            'by_month' => $byMonth,
            'return_rate' => $total > 0 ? round($returned / $total * 100) : 0,
            'by_category' => $byCategory,
            'by_location' => $byLocation,
        ]);
    }

    public function reports(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Item::query()->with('user:id,name');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
            });
        }

        if (in_array($request->input('moderation'), ['pending', 'approved', 'rejected', 'suspended'])) {
            $query->where('moderation_status', $request->input('moderation'));
        }

        return ApiResponse::ok($query
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'image' => $item->image,
                'type' => $item->type,
                'category' => $item->category,
                'location' => $item->location,
                'reporter_name' => $item->user?->name,
                'created_at' => $item->created_at,
                'moderation_status' => $item->moderation_status,
                'moderation_reason' => $item->moderation_reason,
            ]));
    }

    public function moderate(Request $request, Item $item): \Illuminate\Http\JsonResponse
    {
        $action = $request->input('action');

        if (! in_array($action, ['approve', 'reject', 'suspend', 'delete'])) {
            return ApiResponse::fail('Aksi tidak valid.', 422);
        }

        if ($action === 'delete') {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $item->delete();

            return ApiResponse::ok();
        }

        $item->update([
            'moderation_status' => $action === 'approve' ? 'approved' : $action,
            'moderation_reason' => $request->input('reason') ?: null,
        ]);

        return ApiResponse::ok();
    }

    public function users(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::ok(User::query()
            ->withCount(['items as reports_count'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'created_at']));
    }

    public function updateRole(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'in:student,staff,admin'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::fail($validator->errors()->first(), 422);
        }

        if ($request->user()->id === $user->id) {
            return ApiResponse::fail('Kamu tidak dapat mengubah peranmu sendiri.', 403);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return ApiResponse::fail('Tidak dapat menurunkan administrator terakhir.', 400);
        }

        $user->update(['role' => $request->input('role')]);

        return ApiResponse::ok();
    }
}