<?php

namespace App\Models;

use Database\Factories\ShareFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'shareable_type', 'shareable_id', 'shared_by', 'shared_with',
    'permission', 'token', 'expires_at',
])]
class Share extends Model
{
    /** @use HasFactory<ShareFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with');
    }

    public function isPublicLink(): bool
    {
        return $this->shared_with === null && $this->token !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
