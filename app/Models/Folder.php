<?php

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'parent_id', 'name', 'path', 'starred_at'])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'starred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function descendants()
    {
        return self::where('user_id', $this->user_id)
            ->where('path', 'like', $this->path.'/%');
    }
}
