<?php

namespace Tests\Feature\Drive;

use App\Models\File;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VersionTest extends TestCase
{
    use RefreshDatabase;

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
