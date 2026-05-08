<?php

namespace Tests\Feature\Drive;

use App\Models\File;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_root_folder(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('drive.folders.store'), ['name' => 'Docs'])
            ->assertRedirect();

        $this->assertDatabaseHas('folders', [
            'user_id' => $user->id,
            'name' => 'Docs',
            'path' => '/Docs',
            'parent_id' => null,
        ]);
    }

    public function test_user_can_create_nested_folder(): void
    {
        $user = User::factory()->create();
        $parent = Folder::factory()->for($user)->create([
            'name' => 'Docs',
            'path' => '/Docs',
        ]);

        $this->actingAs($user)
            ->post(route('drive.folders.store'), [
                'name' => '2026',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('folders', [
            'user_id' => $user->id,
            'name' => '2026',
            'parent_id' => $parent->id,
            'path' => '/Docs/2026',
        ]);
    }

    public function test_renaming_folder_updates_descendant_paths(): void
    {
        $user = User::factory()->create();
        $root = Folder::factory()->for($user)->create([
            'name' => 'Docs',
            'path' => '/Docs',
        ]);
        $child = Folder::factory()->for($user)->create([
            'name' => '2026',
            'parent_id' => $root->id,
            'path' => '/Docs/2026',
        ]);
        $grandchild = Folder::factory()->for($user)->create([
            'name' => 'Q1',
            'parent_id' => $child->id,
            'path' => '/Docs/2026/Q1',
        ]);

        $this->actingAs($user)
            ->patch(route('drive.folders.update', $root), ['name' => 'Documentos'])
            ->assertRedirect();

        $this->assertSame('/Documentos', $root->fresh()->path);
        $this->assertSame('/Documentos/2026', $child->fresh()->path);
        $this->assertSame('/Documentos/2026/Q1', $grandchild->fresh()->path);
    }

    public function test_moving_folder_updates_descendant_paths(): void
    {
        $user = User::factory()->create();
        $a = Folder::factory()->for($user)->create(['name' => 'A', 'path' => '/A']);
        $b = Folder::factory()->for($user)->create(['name' => 'B', 'path' => '/B']);
        $child = Folder::factory()->for($user)->create([
            'name' => 'inside',
            'parent_id' => $a->id,
            'path' => '/A/inside',
        ]);

        $this->actingAs($user)
            ->patch(route('drive.folders.move', $a), ['parent_id' => $b->id])
            ->assertRedirect();

        $this->assertSame('/B/A', $a->fresh()->path);
        $this->assertSame('/B/A/inside', $child->fresh()->path);
    }

    public function test_deleting_folder_soft_deletes_descendants_and_files(): void
    {
        $user = User::factory()->create();
        $root = Folder::factory()->for($user)->create(['name' => 'Docs', 'path' => '/Docs']);
        $child = Folder::factory()->for($user)->create([
            'name' => 'sub',
            'parent_id' => $root->id,
            'path' => '/Docs/sub',
        ]);
        $file = File::factory()->for($user)->create(['folder_id' => $child->id]);

        $this->actingAs($user)
            ->delete(route('drive.folders.destroy', $root))
            ->assertRedirect();

        $this->assertSoftDeleted($root);
        $this->assertSoftDeleted($child);
        $this->assertSoftDeleted($file);
    }

    public function test_user_cannot_modify_other_users_folder(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $folder = Folder::factory()->for($alice)->create(['name' => 'Secret', 'path' => '/Secret']);

        $this->actingAs($bob)
            ->patch(route('drive.folders.update', $folder), ['name' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame('Secret', $folder->fresh()->name);
    }
}
