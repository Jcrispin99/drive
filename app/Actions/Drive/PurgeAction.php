<?php

namespace App\Actions\Drive;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeAction
{
    public function execute(Model $item): void
    {
        DB::transaction(function () use ($item): void {
            if ($item instanceof Folder) {
                $this->purgeFolder($item);
            } elseif ($item instanceof File) {
                $this->purgeFile($item);
            }
        });
    }

    protected function purgeFolder(Folder $folder): void
    {
        $descendantIds = Folder::withTrashed()
            ->where('user_id', $folder->user_id)
            ->where('path', 'like', $folder->path.'/%')
            ->pluck('id');

        $files = File::withTrashed()
            ->where('user_id', $folder->user_id)
            ->where(function ($q) use ($folder, $descendantIds): void {
                $q->where('folder_id', $folder->id)
                    ->orWhereIn('folder_id', $descendantIds);
            })
            ->get();

        foreach ($files as $file) {
            $this->purgeFile($file);
        }

        if ($descendantIds->isNotEmpty()) {
            Folder::withTrashed()->whereIn('id', $descendantIds)->forceDelete();
        }

        $folder->forceDelete();
    }

    protected function purgeFile(File $file): void
    {
        foreach ($file->versions()->withoutGlobalScopes()->get() as $version) {
            Storage::disk($version->disk)->delete($version->storage_path);
        }

        $file->forceDelete();
    }
}
