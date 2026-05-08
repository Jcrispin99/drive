<?php

namespace App\Models;

use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id', 'folder_id', 'name', 'extension', 'mime_type',
    'size', 'disk', 'current_version_id', 'starred_at', 'last_accessed_at',
])]
class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'starred_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(FileVersion::class)->orderByDesc('version');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(FileVersion::class, 'current_version_id');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
