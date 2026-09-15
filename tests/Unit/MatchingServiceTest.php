<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Services\MatchingService;
use PHPUnit\Framework\TestCase;

class MatchingServiceTest extends TestCase
{
    private function item(array $attributes = []): Item
    {
        return new Item(array_merge([
            'type' => 'lost',
            'name' => 'Black AirPods Pro',
            'category' => 'Electronics',
            'location' => 'Library',
            'date' => '2026-09-10',
        ], $attributes));
    }

    public function test_exact_matching_item_scores_high(): void
    {
        $lost = $this->item();
        $found = $this->item([
            'type' => 'found',
            'name' => 'Black AirPods Pro',
            'category' => 'Electronics',
            'location' => 'Library',
            'date' => '2026-09-11',
        ]);

        $score = MatchingService::score($lost, $found);

        $this->assertGreaterThanOrEqual(50, $score);
    }

    public function test_completely_different_item_scores_below_threshold(): void
    {
        $lost = $this->item();
        $found = $this->item([
            'type' => 'found',
            'name' => 'Red UMRAH shirt',
            'category' => 'Clothing',
            'location' => 'Cafeteria',
            'date' => '2026-08-01',
        ]);

        $score = MatchingService::score($lost, $found);

        $this->assertLessThan(50, $score);
    }

    public function test_score_never_exceeds_max(): void
    {
        $a = $this->item();
        $b = $a->replicate();

        $score = MatchingService::score($a, $b);

        $this->assertLessThanOrEqual(100, $score);
    }
}