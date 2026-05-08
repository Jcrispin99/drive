<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\StoreShareRequest;
use App\Models\File;
use App\Models\Folder;
use App\Models\Share;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShareController extends Controller
{
    public function index(Request $request): Response
    {
        $shares = Share::with(['shareable', 'sharedBy'])
            ->where('shared_with', $request->user()->id)
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();

        $folders = $shares->where('shareable_type', Folder::class)
            ->map(fn (Share $s) => [
                'id' => $s->shareable_id,
                'name' => $s->shareable?->name,
                'path' => $s->shareable?->path,
                'shared_by' => $s->sharedBy?->name,
                'permission' => $s->permission,
            ])->values();

        $files = $shares->where('shareable_type', File::class)
            ->map(fn (Share $s) => [
                'id' => $s->shareable_id,
                'name' => $s->shareable?->name,
                'mime_type' => $s->shareable?->mime_type,
                'size' => $s->shareable?->size,
                'shared_by' => $s->sharedBy?->name,
                'permission' => $s->permission,
            ])->values();

        return Inertia::render('drive/Shared', [
            'folders' => $folders,
            'files' => $files,
        ]);
    }

    public function forItem(Request $request, string $type, int $id): JsonResponse
    {
        $shareableClass = match ($type) {
            'folder' => Folder::class,
            'file' => File::class,
            default => throw new NotFoundHttpException,
        };

        $item = $shareableClass::where('user_id', $request->user()->id)->findOrFail($id);

        $shares = Share::with('sharedWith')
            ->where('shareable_type', $shareableClass)
            ->where('shareable_id', $item->id)
            ->where('shared_by', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Share $s) => $this->formatShare($s));

        return response()->json(['shares' => $shares]);
    }

    public function store(StoreShareRequest $request): RedirectResponse|JsonResponse
    {
        $shareableClass = $request->shareableClass();

        $shareable = $shareableClass::where('user_id', $request->user()->id)
            ->findOrFail($request->shareable_id);

        $sharedWith = null;
        if (! $request->boolean('public_link') && $request->shared_with_email) {
            $sharedWith = User::where('email', $request->shared_with_email)->first();
            abort_unless($sharedWith, 422);
            abort_if($sharedWith->id === $request->user()->id, 422);
        }

        $share = Share::create([
            'shareable_type' => $shareableClass,
            'shareable_id' => $shareable->id,
            'shared_by' => $request->user()->id,
            'shared_with' => $sharedWith?->id,
            'permission' => $request->permission,
            'token' => $request->boolean('public_link') ? Str::random(40) : null,
            'expires_at' => $request->expires_at,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['share' => $this->formatShare($share->load('sharedWith'))], 201);
        }

        return back();
    }

    public function update(Request $request, Share $share): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $share);

        $data = $request->validate([
            'permission' => ['required', Rule::in(['view', 'edit'])],
            'expires_at' => ['nullable', 'date'],
        ]);

        $share->update($data);

        if ($request->wantsJson()) {
            return response()->json(['share' => $this->formatShare($share->fresh()->load('sharedWith'))]);
        }

        return back();
    }

    public function destroy(Request $request, Share $share): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $share);

        $share->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatShare(Share $share): array
    {
        return [
            'id' => $share->id,
            'permission' => $share->permission,
            'shared_with' => $share->sharedWith
                ? $share->sharedWith->only(['id', 'name', 'email'])
                : null,
            'token' => $share->token,
            'public_url' => $share->token ? url('/s/'.$share->token) : null,
            'is_public_link' => $share->shared_with === null && $share->token !== null,
            'expires_at' => $share->expires_at?->toIso8601String(),
        ];
    }
}
