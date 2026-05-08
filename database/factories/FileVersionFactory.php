<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FileVersion>
 */
class FileVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'file_id' => File::factory(),
            'version' => 1,
            'disk' => 'local',
            'storage_path' => 'drive/'.Str::uuid()->toString(),
            'size' => fake()->numberBetween(1024, 5_000_000),
            'checksum' => fake()->sha256(),
            'uploaded_by' => User::factory(),
            'created_at' => now(),
        ];
    }
}
