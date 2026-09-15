<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Item;
use Carbon\CarbonImmutable;

final class MatchingService
{
    private const WEIGHT_CATEGORY = 30;
    private const WEIGHT_LOCATION = 30;
    private const WEIGHT_TIME = 20;
    private const WEIGHT_TEXT = 20;
    private const THRESHOLD = 50;
    private const MAX_MATCHES = 6;

    /**
     * Candidate items of the opposite type that could match this item.
     *
     * @return array<int, array{item: array<string, mixed>, score: int, label: string}>
     */
    public static function forItem(Item $item): array
    {
        return self::candidates($item)
            ->map(fn (Item $candidate) => [
                'item' => self::publicItem($candidate),
                'score' => self::score($item, $candidate),
            ])
            ->filter(fn (array $m) => $m['score'] >= self::THRESHOLD)
            ->sortByDesc('score')
            ->take(self::MAX_MATCHES)
            ->values()
            ->map(fn (array $m) => $m + ['label' => self::label($m['score'])])
            ->all();
    }

    /**
     * When a new found item is published, alert the owners of matching lost items.
     */
    public static function notifyMatchesFor(Item $item): void
    {
        if ($item->type !== 'found') {
            return;
        }

        foreach (self::forItem($item) as $match) {
            AppNotification::create([
                'user_id' => $match['item']['user_id'],
                'type' => 'possible_match',
                'title' => 'Kemungkinan cocok ditemukan!',
                'message' => "Laporan lain mungkin cocok dengan barang hilangmu '{$match['item']['name']}'.",
                'reference_id' => $item->id,
            ]);
        }
    }

    public static function score(Item $a, Item $b): int
    {
        $score = 0;

        $score += $a->category === $b->category ? self::WEIGHT_CATEGORY : 0;
        $score += $a->location === $b->location ? self::WEIGHT_LOCATION : 0;
        $score += (int) round(self::WEIGHT_TIME * self::timeScore($a->date, $b->date));
        $score += (int) round(self::WEIGHT_TEXT * self::textScore($a->name, $b->name));

        return min(100, $score);
    }

    private static function candidates(Item $item)
    {
        return Item::query()
            ->whereKeyNot($item->id)
            ->where('moderation_status', 'approved')
            ->whereIn('status', ['lost', 'found'])
            ->where('type', $item->type === 'lost' ? 'found' : 'lost')
            ->get();
    }

    private static function timeScore(string $dateA, string $dateB): float
    {
        $days = abs(CarbonImmutable::parse($dateA)->diffInDays(CarbonImmutable::parse($dateB)));

        return match (true) {
            $days <= 1 => 1.0,
            $days <= 3 => 0.7,
            $days <= 7 => 0.3,
            default => 0.0,
        };
    }

    private static function textScore(string $nameA, string $nameB): float
    {
        $a = mb_strtolower(trim($nameA));
        $b = mb_strtolower(trim($nameB));

        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    private static function label(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Kecocokan kuat',
            $score >= 65 => 'Kecocokan besar',
            default => 'Ada kemungkinan cocok',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function publicItem(Item $item): array
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
}