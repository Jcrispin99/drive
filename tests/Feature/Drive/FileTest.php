<?php

namespace Tests\Feature\Drive;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_rename_file(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->for($user)->create(['name' => 'old.txt', 'extension' => 'txt']);

        $this->actingAs($user)
            ->patch(route('drive.files.update', $file), ['name' => 'new.txt'])
            ->assertRedirect();

        $this->assertSame('new.txt', $file->fresh()->name);
    }

    public function test_user_can_move_file_between_folders(): void
    {
        $user = User::factory()->create();
        $a = Folder::factory()->for($user)->create(['name' => 'A', 'path' => '/A']);
        $b = Folder::factory()->for($user)->create(['name' => 'B', 'path' => '/B']);
        $file = File::factory()->for($user)->create(['folder_id' => $a->id]);

        $this->actingAs($user)
            ->patch(route('drive.files.move', $file), ['parent_id' => $b->id])
            ->assertRedirect();

        $this->assertSame($b->id, $file->fresh()->folder_id);
    }

    public function test_user_can_download_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('drive.upload.store'), [
            'file' => TestFile::create('hello.txt', 5),
        ])->assertOk();

        $file = File::firstWhere('name', 'hello.txt');

        $this->actingAs($user)
            ->get(route('drive.files.download', $file))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=hello.txt');

        $this->assertNotNull($file->fresh()->last_accessed_at);
    }

    public function test_deleting_file_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('drive.files.destroy', $file))
            ->assertRedirect();

        $this->assertSoftDeleted($file);
    }

    public function test_user_cannot_download_other_users_file(): void
    {
        Storage::fake('local');
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post(route('drive.upload.store'), [
            'file' => TestFile::create('private.txt', 5),
        ])->assertOk();

        $file = File::firstWhere('name', 'private.txt');

        $this->actingAs($bob)
            ->get(route('drive.files.download', $file))
            ->assertForbidden();
    }

    public function test_user_can_toggle_star_on_file(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('drive.star.toggle', ['type' => 'file', 'id' => $file->id]))
            ->assertRedirect();

        $this->assertNotNull($file->fresh()->starred_at);

        $this->actingAs($user)
            ->post(route('drive.star.toggle', ['type' => 'file', 'id' => $file->id]))
            ->assertRedirect();

        $this->assertNull($file->fresh()->starred_at);
    }

    public function test_user_can_restore_file_from_trash(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->for($user)->create();
        $file->delete();

        $this->actingAs($user)
            ->post(route('drive.trash.restore', ['type' => 'file', 'id' => $file->id]))
            ->assertRedirect();

        $this->assertNotSoftDeleted($file);
    }

    public function test_purging_file_removes_storage(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('drive.upload.store'), [
            'file' => TestFile::create('bye.txt', 5),
        ])->assertOk();

        $file = File::firstWhere('name', 'bye.txt');
        $version = $file->currentVersion;
        $file->delete();

        $this->actingAs($user)
            ->delete(route('drive.trash.purge', ['type' => 'file', 'id' => $file->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($version->storage_path);
    }
}
