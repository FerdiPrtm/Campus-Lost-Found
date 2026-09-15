<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Services\MatchingService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    private const SORTS = ['newest', 'oldest', 'recently_updated'];
    private const STATUSES = ['lost', 'found', 'claimed', 'verified', 'returned'];

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Item::query()
            ->where('moderation_status', 'approved')
            ->with('user:id,name');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('location', 'like', "%{$q}%");
            });
        }

        if (in_array($request->input('status'), self::STATUSES)) {
            $query->where('status', $request->input('status'));
        }

        if (in_array($request->input('type'), ['lost', 'found'])) {
            $query->where('type', $request->input('type'));
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($location = $request->input('location')) {
            $query->where('location', $location);
        }

        $query->orderBy('created_at', match ($request->input('sort')) {
            'oldest' => 'asc',
            'recently_updated' => 'desc',
            default => 'desc',
        });

        if ($request->input('sort') === 'recently_updated') {
            $query->orderByDesc('updated_at');
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 8)));

        $items = $query->paginate($perPage, ['*'], 'page', $page);

        return ApiResponse::ok([
            'items' => collect($items->items())->map(fn (Item $item) => $this->publicItem($item))->all(),
            'pagination' => [
                'total' => $items->total(),
                'page' => $items->currentPage(),
                'total_pages' => $items->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $item = Item::with('user:id,name')->findOrFail($id);

        $user = $request->user();
        $isOwner = $user && $item->user_id === $user->id;
        $isStaff = $user && $user->isStaff();

        if ($item->moderation_status !== 'approved' && ! $isOwner && ! $isStaff) {
            return ApiResponse::fail('Tidak ditemukan.', 404);
        }

        return ApiResponse::ok($this->detailItem($item, $isOwner || $isStaff));
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:lost,found'],
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', 'string', 'exists:categories,name'],
            'location' => ['required', 'string', 'exists:locations,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'storage_location' => ['nullable', 'string', 'max:191'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'verification_answers' => ['nullable', 'json'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::fail($validator->errors()->first(), 422);
        }

        $data = $validator->validated();

        $answers = collect(json_decode($data['verification_answers'] ?? '[]', true) ?: [])
            ->filter(fn (array $qa) => trim((string) ($qa['q'] ?? '')) !== '' && trim((string) ($qa['a'] ?? '')) !== '')
            ->values()
            ->all();

        $image = $request->hasFile('image')
            ? $request->file('image')->store('items', 'public')
            : null;

        $status = $data['type'] === 'found' ? 'found' : 'lost';

        $item = Item::create([
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'name' => $data['name'],
            'category' => $data['category'],
            'location' => $data['location'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'time' => $data['time'] ?? null,
            'storage_location' => $data['storage_location'] ?? null,
            'image' => $image,
            'verification_answers' => $answers ?: null,
            'status' => $status,
            'moderation_status' => 'pending',
        ]);

        MatchingService::notifyMatchesFor($item);

        foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
            AppNotification::create([
                'user_id' => $adminId,
                'type' => 'new_report',
                'title' => 'Laporan baru masuk',
                'message' => "{$request->user()->name} melaporkan '{$data['name']}' (".($data['type'] === 'lost' ? 'Hilang' : 'Ditemukan').').',
                'reference_id' => $item->id,
            ]);
        }

        return ApiResponse::ok($this->publicItem($item), 201);
    }

    public function update(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $item = Item::with('user:id,name')->findOrFail($id);

        $user = $request->user();
        if ($item->user_id !== $user->id && ! $user->isStaff()) {
            return ApiResponse::fail('Kamu tidak memiliki izin untuk melakukan aksi ini.', 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'name' => ['nullable', 'string', 'max:191'],
            'category' => ['nullable', 'string', 'exists:categories,name'],
            'location' => ['nullable', 'string', 'exists:locations,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'storage_location' => ['nullable', 'string', 'max:191'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::fail($validator->errors()->first(), 422);
        }

        $data = collect($validator->validated())->filter(fn ($value) => $value !== null)->all();

        $wasReturned = false;
        if (isset($data['status']) && $data['status'] === 'returned' && $item->status !== 'returned') {
            $wasReturned = true;
        }

        $item->update($data);

        $item->load('user:id,name');

        if ($wasReturned) {
            $approved = $item->claims()->where('status', 'approved')->latest()->first();
            if ($approved) {
                \App\Models\AppNotification::create([
                    'user_id' => $approved->user_id,
                    'type' => 'item_returned',
                    'title' => 'Barang dikembalikan',
                    'message' => "Barang '{$item->name}' telah ditandai dikembalikan.",
                    'reference_id' => $item->id,
                ]);
            }
        }

        return ApiResponse::ok($this->detailItem($item, true));
    }

    public function destroy(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $item = Item::findOrFail($id);

        $user = $request->user();
        if ($item->user_id !== $user->id && ! $user->isStaff()) {
            return ApiResponse::fail('Kamu tidak memiliki izin untuk melakukan aksi ini.', 403);
        }

        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return ApiResponse::ok();
    }

    /**
     * @return array<string, mixed>
     */
    private function publicItem(Item $item): array
    {
        return [
            'id' => $item->id,
            'user_id' => $item->user_id,
            'name' => $item->name,
            'type' => $item->type,
            'status' => $item->status,
            'category' => $item->category,
            'location' => $item->location,
            'date' => $item->date,
            'description' => $item->description,
            'image' => $item->image,
            'created_at' => $item->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailItem(Item $item, bool $includeAnswers): array
    {
        $answers = $item->verification_answers;

        if ($answers && ! $includeAnswers) {
            $answers = collect($answers)->map(fn (array $qa) => ['q' => $qa['q']])->all();
        }

        return $this->publicItem($item) + [
            'user' => $item->user ? ['id' => $item->user->id, 'name' => $item->user->name] : null,
            'storage_location' => $item->storage_location,
            'moderation_status' => $item->moderation_status,
            'verification_answers' => $answers,
            'matches' => MatchingService::forItem($item),
        ];
    }
}