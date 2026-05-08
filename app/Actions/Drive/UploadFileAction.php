<?php

namespace App\Actions\Drive;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadFileAction
{
    public function execute(User $user, UploadedFile $upload, ?Folder $folder = null): File
    {
        return DB::transaction(function () use ($user, $upload, $folder): File {
            $disk = 'local';
            $storagePath = sprintf('drive/%d/%s', $user->id, Str::uuid()->toString());

            Storage::disk($disk)->putFileAs(
                dirname($storagePath),
                $upload,
                basename($storagePath)
            );

            $file = File::create([
                'user_id' => $user->id,
                'folder_id' => $folder?->id,
                'name' => $upload->getClientOriginalName(),
                'extension' => $upload->getClientOriginalExtension() ?: null,
                'mime_type' => $upload->getClientMimeType() ?: 'application/octet-stream',
                'size' => $upload->getSize() ?: 0,
                'disk' => $disk,
            ]);

            $version = FileVersion::create([
                'file_id' => $file->id,
                'version' => 1,
                'disk' => $disk,
                'storage_path' => $storagePath,
                'size' => $file->size,
                'checksum' => @hash_file('sha256', $upload->getRealPath()) ?: null,
                'uploaded_by' => $user->id,
            ]);

            $file->update(['current_version_id' => $version->id]);

            return $file->refresh();
        });
    }

    public function addVersion(User $user, File $file, UploadedFile $upload): FileVersion
    {
        return DB::transaction(function () use ($user, $file, $upload): FileVersion {
            $disk = $file->disk;
            $storagePath = sprintf('drive/%d/%s', $file->user_id, Str::uuid()->toString());

            Storage::disk($disk)->putFileAs(
                dirname($storagePath),
                $upload,
                basename($storagePath)
            );

            $nextVersion = ($file->versions()->max('version') ?? 0) + 1;

            $version = FileVersion::create([
                'file_id' => $file->id,
                'version' => $nextVersion,
                'disk' => $disk,
                'storage_path' => $storagePath,
                'size' => $upload->getSize() ?: 0,
                'checksum' => @hash_file('sha256', $upload->getRealPath()) ?: null,
                'uploaded_by' => $user->id,
            ]);

            $file->update([
                'current_version_id' => $version->id,
                'size' => $version->size,
                'mime_type' => $upload->getClientMimeType() ?: $file->mime_type,
            ]);

            return $version;
        });
    }
}
