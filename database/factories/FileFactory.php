<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'png', 'jpg', 'txt', 'docx']);
        $name = fake()->unique()->words(2, true).'.'.$extension;

        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'txt' => 'text/plain',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };

        return [
            'user_id' => User::factory(),
            'folder_id' => null,
            'name' => $name,
            'extension' => $extension,
            'mime_type' => $mime,
            'size' => fake()->numberBetween(1024, 5_000_000),
            'disk' => 'local',
            'starred_at' => null,
            'last_accessed_at' => null,
        ];
    }
}
