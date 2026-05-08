<?php

namespace Tests\Feature\Drive;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_a_single_file_to_root(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('drive.upload.store'), [
                'file' => TestFile::create('hello.txt', 5),
            ])
            ->assertOk();

        $file = File::firstWhere('name', 'hello.txt');
        $this->assertNotNull($file);
        $this->assertSame($user->id, $file->user_id);
        $this->assertNull($file->folder_id);
        $this->assertNotNull($file->current_version_id);
        $this->assertSame(1, $file->currentVersion->version);
        Storage::disk('local')->assertExists($file->currentVersion->storage_path);
    }

    public function test_user_can_upload_into_a_specific_folder(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $folder = Folder::factory()->for($user)->create(['name' => 'Docs', 'path' => '/Docs']);

        $this->actingAs($user)
            ->post(route('drive.upload.store'), [
                'file' => TestFile::create('a.pdf', 10),
                'parent_id' => $folder->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('files', [
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'name' => 'a.pdf',
        ]);
    }

    public function test_uploading_with_relative_path_creates_folder_hierarchy(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('drive.upload.store'), [
                'file' => TestFile::create('foo.txt', 5),
                'relative_path' => 'MyFolder/Sub/foo.txt',
            ])
            ->assertOk();

        $this->assertDatabaseHas('folders', [
            'user_id' => $user->id,
            'name' => 'MyFolder',
            'parent_id' => null,
            'path' => '/MyFolder',
        ]);

        $sub = Folder::where('user_id', $user->id)->where('name', 'Sub')->first();
        $this->assertNotNull($sub);
        $this->assertSame('/MyFolder/Sub', $sub->path);

        $file = File::firstWhere('name', 'foo.txt');
        $this->assertSame($sub->id, $file->folder_id);
    }

    public function test_uploads_reuse_existing_folder_hierarchy(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('drive.upload.store'), [
                'file' => TestFile::create('a.txt', 5),
                'relative_path' => 'X/Y/a.txt',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->post(route('drive.upload.store'), [
                'file' => TestFile::create('b.txt', 5),
                'relative_path' => 'X/Y/b.txt',
            ])
            ->assertOk();

        $this->assertSame(2, Folder::where('user_id', $user->id)->count());
        $this->assertSame(2, File::where('user_id', $user->id)->count());
    }
}
