<?php

use App\Http\Controllers\Drive\DriveController;
use App\Http\Controllers\Drive\FileController;
use App\Http\Controllers\Drive\FolderController;
use App\Http\Controllers\Drive\PublicShareController;
use App\Http\Controllers\Drive\RecentController;
use App\Http\Controllers\Drive\SearchController;
use App\Http\Controllers\Drive\ShareController;
use App\Http\Controllers\Drive\StarController;
use App\Http\Controllers\Drive\StarredController;
use App\Http\Controllers\Drive\TrashController;
use App\Http\Controllers\Drive\UploadController;
use App\Http\Controllers\Drive\VersionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('drive')->name('drive.')->group(function (): void {
    Route::get('/', [DriveController::class, 'index'])->name('index');
    Route::get('folders/{folder}', [DriveController::class, 'show'])->name('folders.show');

    Route::post('folders', [FolderController::class, 'store'])->name('folders.store');
    Route::patch('folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::patch('folders/{folder}/move', [FolderController::class, 'move'])->name('folders.move');
    Route::delete('folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    Route::post('upload', [UploadController::class, 'store'])->name('upload.store');

    Route::patch('files/{file}', [FileController::class, 'update'])->name('files.update');
    Route::patch('files/{file}/move', [FileController::class, 'move'])->name('files.move');
    Route::delete('files/{file}', [FileController::class, 'destroy'])->name('files.destroy');
    Route::get('files/{file}/download', [FileController::class, 'download'])->name('files.download');
    Route::get('files/{file}/preview', [FileController::class, 'preview'])->name('files.preview');

    Route::post('files/{file}/versions', [VersionController::class, 'store'])->name('files.versions.store');
    Route::post('files/{file}/versions/{version}/restore', [VersionController::class, 'restore'])
        ->name('files.versions.restore');

    Route::post('star/{type}/{id}', [StarController::class, 'toggle'])
        ->whereIn('type', ['file', 'folder'])
        ->name('star.toggle');

    Route::get('shared', [ShareController::class, 'index'])->name('shared.index');
    Route::get('shares/for/{type}/{id}', [ShareController::class, 'forItem'])
        ->whereIn('type', ['file', 'folder'])
        ->name('shares.for-item');
    Route::post('shares', [ShareController::class, 'store'])->name('shares.store');
    Route::patch('shares/{share}', [ShareController::class, 'update'])->name('shares.update');
    Route::delete('shares/{share}', [ShareController::class, 'destroy'])->name('shares.destroy');

    Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore'])
        ->whereIn('type', ['file', 'folder'])
        ->name('trash.restore');
    Route::delete('trash/{type}/{id}', [TrashController::class, 'purge'])
        ->whereIn('type', ['file', 'folder'])
        ->name('trash.purge');
    Route::delete('trash', [TrashController::class, 'empty'])->name('trash.empty');

    Route::get('recent', [RecentController::class, 'index'])->name('recent.index');
    Route::get('starred', [StarredController::class, 'index'])->name('starred.index');
    Route::get('search', [SearchController::class, 'index'])->name('search.index');
});

Route::get('s/{token}', [PublicShareController::class, 'show'])->name('public.share.show');
Route::get('s/{token}/download', [PublicShareController::class, 'download'])->name('public.share.download');
