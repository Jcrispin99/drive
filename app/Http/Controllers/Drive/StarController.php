<?php

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StarController extends Controller
{
    public function toggle(Request $request, string $type, int $id): RedirectResponse
    {
        $model = match ($type) {
            'file' => File::class,
            'folder' => Folder::class,
            default => throw new NotFoundHttpException,
        };

        $item = $model::where('user_id', $request->user()->id)->findOrFail($id);

        $item->update([
            'starred_at' => $item->starred_at ? null : now(),
        ]);

        return back();
    }
}
