<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Share>
 */
class ShareFactory extends Factory
{
    public function definition(): array
    {
        $shareable = File::factory()->create();

        return [
            'shareable_type' => File::class,
            'shareable_id' => $shareable->id,
            'shared_by' => $shareable->user_id,
            'shared_with' => User::factory(),
            'permission' => 'view',
            'token' => null,
            'expires_at' => null,
        ];
    }

    public function publicLink(): static
    {
        return $this->state(fn () => [
            'shared_with' => null,
            'token' => Str::random(40),
        ]);
    }

    public function canEdit(): static
    {
        return $this->state(fn () => ['permission' => 'edit']);
    }
}
