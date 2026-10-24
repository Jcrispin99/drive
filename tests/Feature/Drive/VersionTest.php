<?php

namespace Tests\Feature\Drive;

use App\Models\File;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class VersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_a_large_powerpoint_version(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $file = File::factory()->for($user)->create(['name' => 'slides.pptx']);

        $this->actingAs($user)->post(route('drive.files.versions.store', $file), [
            'file' => TestFile::create('slides.pptx', 1024 * 1024, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $file->refresh();
        $this->assertSame(1024 * 1024 * 1024, $file->size);
        Storage::disk('local')->assertExists($file->currentVersion->storage_path);
    }

    public function test_versions_respect_the_configured_upload_limit(): void
    {
        config(['drive.max_upload_size_mb' => 200]);
        Storage::fake('local');
        $user = User::factory()->create();
        $file = File::factory()->for($user)->create();

        $this->actingAs($user)->postJson(route('drive.files.versions.store', $file), [
            'file' => TestFile::create('slides.pptx', 200 * 1024 + 1),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('file_versions', 0);
        $this->assertNull($file->fresh()->current_version_id);
    }

    public function test_failed_version_storage_preserves_the_previous_version(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('drive.upload.store'), [
            'file' => TestFile::create('slides.pptx', 10),
        ])->assertOk();
        $file = File::firstOrFail();
        $previousVersionId = $file->current_version_id;

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);

        $this->postJson(route('drive.files.versions.store', $file), [
            'file' => TestFile::create('slides.pptx', 500 * 1024),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertSame($previousVersionId, $file->fresh()->current_version_id);
        $this->assertSame(10 * 1024, $file->fresh()->size);
        $this->assertDatabaseCount('file_versions', 1);
    }

    public function test_user_can_upload_a_new_version(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('drive.upload.store'), [
            'file' => TestFile::create('doc.txt', 10),
        ])->assertOk();

        $file = File::firstWhere('name', 'doc.txt');
        $v1Id = $file->current_version_id;

        $this->actingAs($user)
            ->post(route('drive.files.versions.store', $file), [
                'file' => TestFile::create('doc-v2.txt', 20),
            ])
            ->assertRedirect();

        $file->refresh();
        $this->assertNotSame($v1Id, $file->current_version_id);
        $this->assertSame(2, $file->versions()->count());
        $this->assertSame(2, $file->currentVersion->version);
    }

    public function test_user_can_restore_previous_version(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('drive.upload.store'), [
            'file' => TestFile::create('doc.txt', 10),
        ])->assertOk();

        $file = File::firstWhere('name', 'doc.txt');
        $v1 = $file->currentVersion;

        $this->actingAs($user)->post(route('drive.files.versions.store', $file), [
            'file' => TestFile::create('doc-v2.txt', 20),
        ])->assertRedirect();

        $this->actingAs($user)
            ->post(route('drive.files.versions.restore', ['file' => $file->id, 'version' => $v1->id]))
            ->assertRedirect();

        $this->assertSame($v1->id, $file->fresh()->current_version_id);
    }
}
