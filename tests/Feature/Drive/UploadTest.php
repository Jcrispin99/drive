<?php

namespace Tests\Feature\Drive;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('powerPointFiles')]
    public function test_user_can_upload_large_powerpoint_files(string $name, string $mimeType): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('drive.upload.store'), [
            'file' => TestFile::create($name, 512 * 1024, $mimeType),
        ])->assertOk()->assertJsonPath('file.name', $name);

        $file = File::firstWhere('name', $name);
        $this->assertSame(512 * 1024 * 1024, $file->size);
        $this->assertSame(pathinfo($name, PATHINFO_EXTENSION), $file->extension);
        $this->assertSame($mimeType, $file->mime_type);
        Storage::disk('local')->assertExists($file->currentVersion->storage_path);
        $this->get(route('drive.files.download', $file))->assertOk()->assertDownload($name);
    }

    public static function powerPointFiles(): array
    {
        return [
            'PPT' => ['slides.ppt', 'application/vnd.ms-powerpoint'],
            'PPTX' => ['slides.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        ];
    }

    public function test_powerpoint_with_generic_mime_is_stored_and_downloaded_without_changes(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $content = "PowerPoint upload contents\0\xff";
        $temporaryFile = TestFile::createWithContent('slides.pptx', $content);
        $upload = new UploadedFile($temporaryFile->getPathname(), 'slides.pptx', 'application/octet-stream', null, true);

        $this->actingAs($user)->postJson(route('drive.upload.store'), [
            'file' => $upload,
        ])->assertOk();

        $file = File::firstOrFail();
        $this->assertSame('application/octet-stream', $file->mime_type);
        $this->assertSame(hash('sha256', $content), $file->currentVersion->checksum);
        $this->get(route('drive.files.download', $file))
            ->assertOk()->assertDownload('slides.pptx')->assertStreamedContent($content);
    }

    public function test_user_can_upload_a_file_at_the_one_gigabyte_limit(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('drive.upload.store'), [
            'file' => TestFile::create('large.zip', 1024 * 1024),
        ])->assertOk();

        $this->assertSame(1024 * 1024 * 1024, File::firstOrFail()->size);
    }

    public function test_file_above_the_limit_is_rejected_without_creating_files_or_folders(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('drive.upload.store'), [
            'file' => TestFile::create('too-large.pptx', 1024 * 1024 + 1),
            'relative_path' => 'Presentations/too-large.pptx',
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('files', 0);
        $this->assertDatabaseCount('file_versions', 0);
        $this->assertDatabaseCount('folders', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_configured_limit_is_used_by_validation_and_shared_with_the_browser(): void
    {
        config(['drive.max_upload_size_mb' => 200]);
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('drive.upload.store'), [
            'file' => TestFile::create('large.pptx', 200 * 1024 + 1),
        ])->assertUnprocessable()->assertJsonPath('errors.file.0', 'El archivo no debe superar 200 MB.');

        $this->get(route('drive.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->component('drive/Index')
            ->where('driveUpload.maxSizeMb', 200)
        );
    }

    public function test_failed_storage_does_not_report_a_successful_upload(): void
    {
        $user = User::factory()->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);

        $this->actingAs($user)->postJson(route('drive.upload.store'), [
            'file' => TestFile::create('slides.pptx', 500 * 1024),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('files', 0);
        $this->assertDatabaseCount('file_versions', 0);
    }

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
