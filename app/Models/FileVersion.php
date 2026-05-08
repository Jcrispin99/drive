<?php

namespace App\Models;

use Database\Factories\FileVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'file_id', 'version', 'disk', 'storage_path',
    'size', 'checksum', 'uploaded_by',
])]
class FileVersion extends Model
{
    /** @use HasFactory<FileVersionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'size' => 'integer',
            'version' => 'integer',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
