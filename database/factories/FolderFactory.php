<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => $name,
            'path' => '/'.$name,
            'starred_at' => null,
        ];
    }

    public function child(Folder $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $parent->user_id,
            'parent_id' => $parent->id,
            'path' => rtrim($parent->path, '/').'/'.$attributes['name'],
        ]);
    }
}
