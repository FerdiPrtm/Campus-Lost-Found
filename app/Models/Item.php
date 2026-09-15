<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'name', 'category', 'location', 'description', 'date', 'time', 'storage_location', 'image', 'status', 'moderation_status', 'moderation_reason'])]
#[Hidden(['pivot'])]
class Item extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Item $item) {
            $item->status = $item->status ?? $item->type;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}