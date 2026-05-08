<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use App\Models\Share;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicShareController extends Controller
{
    public function show(string $token): Response
    {
        $share = $this->resolve($token);

        $payload = ['type' => null, 'item' => null, 'token' => $token];

        if ($share->shareable_type === File::class) {
            $file = $share->shareable;
            $payload = [
                'type' => 'file',
                'item' => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                ],
                'token' => $token,
            ];
        } elseif ($share->shareable_type === Folder::class) {
            $folder = $share->shareable;
            $payload = [
                'type' => 'folder',
                'item' => [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'path' => $folder->path,
                ],
                'token' => $token,
                'children' => $folder->children()->orderBy('name')->get(['id', 'name'])->all(),
                'files' => $folder->files()->orderBy('name')->get(['id', 'name', 'mime_type', 'size'])->all(),
            ];
        }

        return Inertia::render('drive/Public', $payload);
    }

    public function download(string $token): StreamedResponse
    {
        $share = $this->resolve($token);

        abort_unless($share->shareable_type === File::class, 404);

        $file = $share->shareable;
        $version = $file->currentVersion;
        abort_unless($version, 404);

        return Storage::disk($version->disk)->download($version->storage_path, $file->name);
    }

    protected function resolve(string $token): Share
    {
        $share = Share::where('token', $token)->whereNull('shared_with')->first();

        abort_unless($share, 404);
        abort_if($share->isExpired(), 410);

        return $share;
    }
}
