<?php

namespace Tests\Feature\Drive;

use App\Models\File;
use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File as TestFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_share_file_with_another_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $file = File::factory()->for($alice)->create();

        $this->actingAs($alice)
            ->post(route('drive.shares.store'), [
                'shareable_type' => 'file',
                'shareable_id' => $file->id,
                'shared_with_email' => $bob->email,
                'permission' => 'view',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shares', [
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
            'shared_with' => $bob->id,
            'permission' => 'view',
        ]);
    }

    public function test_shared_user_appears_in_shared_index(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $file = File::factory()->for($alice)->create(['name' => 'shared.txt']);

        Share::create([
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
            'shared_with' => $bob->id,
            'permission' => 'view',
        ]);

        $this->actingAs($bob)
            ->get(route('drive.shared.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->component('drive/Shared')
                    ->has('files', 1)
                    ->where('files.0.name', 'shared.txt')
            );
    }

    public function test_recipient_can_view_shared_file(): void
    {
        Storage::fake('local');
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->post(route('drive.upload.store'), [
            'file' => TestFile::create('hi.txt', 5),
        ])->assertOk();

        $file = File::firstWhere('name', 'hi.txt');

        Share::create([
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
            'shared_with' => $bob->id,
            'permission' => 'view',
        ]);

        $this->actingAs($bob)
            ->get(route('drive.files.download', $file))
            ->assertOk();
    }

    public function test_non_recipient_cannot_view_shared_file(): void
    {
        Storage::fake('local');
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $charlie = User::factory()->create();

        $this->actingAs($alice)->post(route('drive.upload.store'), [
            'file' => TestFile::create('hi.txt', 5),
        ])->assertOk();

        $file = File::firstWhere('name', 'hi.txt');

        Share::create([
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
            'shared_with' => $bob->id,
            'permission' => 'view',
        ]);

        $this->actingAs($charlie)
            ->get(route('drive.files.download', $file))
            ->assertForbidden();
    }

    public function test_public_link_grants_anonymous_access(): void
    {
        Storage::fake('local');
        $alice = User::factory()->create();

        $this->actingAs($alice)->post(route('drive.upload.store'), [
            'file' => TestFile::create('public.txt', 5),
        ])->assertOk();

        $file = File::firstWhere('name', 'public.txt');

        $share = Share::factory()->publicLink()->create([
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
        ]);

        $this->get(route('public.share.show', $share->token))
            ->assertOk();

        $this->get(route('public.share.download', $share->token))
            ->assertOk();
    }

    public function test_expired_share_returns_410(): void
    {
        $alice = User::factory()->create();
        $share = Share::factory()->publicLink()->create([
            'shareable_type' => File::class,
            'shareable_id' => File::factory()->for($alice)->create()->id,
            'shared_by' => $alice->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('public.share.show', $share->token))
            ->assertStatus(410);
    }

    public function test_store_returns_json_with_token_when_requested(): void
    {
        $alice = User::factory()->create();
        $file = File::factory()->for($alice)->create();

        $this->actingAs($alice)
            ->postJson(route('drive.shares.store'), [
                'shareable_type' => 'file',
                'shareable_id' => $file->id,
                'public_link' => true,
                'permission' => 'view',
            ])
            ->assertCreated()
            ->assertJsonPath('share.is_public_link', true)
            ->assertJsonStructure(['share' => ['id', 'token', 'public_url', 'permission']]);
    }

    public function test_owner_can_list_shares_for_a_file(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $file = File::factory()->for($alice)->create();

        Share::factory()->create([
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
            'shared_with' => $bob->id,
            'permission' => 'edit',
        ]);
        Share::factory()->publicLink()->create([
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
        ]);

        $this->actingAs($alice)
            ->getJson(route('drive.shares.for-item', ['type' => 'file', 'id' => $file->id]))
            ->assertOk()
            ->assertJsonCount(2, 'shares');
    }

    public function test_non_owner_cannot_list_shares_of_a_file(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $file = File::factory()->for($alice)->create();

        $this->actingAs($bob)
            ->getJson(route('drive.shares.for-item', ['type' => 'file', 'id' => $file->id]))
            ->assertNotFound();
    }

    public function test_destroy_returns_json_when_requested(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $file = File::factory()->for($alice)->create();
        $share = Share::factory()->create([
            'shareable_type' => File::class,
            'shareable_id' => $file->id,
            'shared_by' => $alice->id,
            'shared_with' => $bob->id,
        ]);

        $this->actingAs($alice)
            ->deleteJson(route('drive.shares.destroy', $share))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('shares', ['id' => $share->id]);
    }
}
