<?php

namespace Database\Factories;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Upload>
 */
class UploadFactory extends Factory
{
    public function definition(): array
    {
        $isPrivate = fake()->boolean();
        $disk = $isPrivate ? 'private_uploads' : 'public_uploads';
        $extension = fake()->randomElement(['jpg', 'png', 'pdf', 'docx', 'xlsx']);
        $storedName = Str::uuid().'.'.$extension;

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return [
            'user_id' => User::factory(),
            'slug' => Str::slug(now()->format('Y-m-d').'-'.Str::random(8)),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(10),
            'is_private' => $isPrivate ? Upload::PRIVATE_UPLOAD : Upload::PUBLIC_UPLOAD,
            'original_name' => fake()->word().'.'.$extension,
            'stored_name' => $storedName,
            'mime_type' => $mimeTypes[$extension],
            'size' => fake()->numberBetween(1024, 10485760),
            'path' => $storedName,
            'disk' => $disk,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => Upload::PUBLIC_UPLOAD,
            'disk' => 'public_uploads',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => Upload::PRIVATE_UPLOAD,
            'disk' => 'private_uploads',
        ]);
    }

    public function image(): static
    {
        $extension = fake()->randomElement(['jpg', 'png', 'gif']);
        $storedName = Str::uuid().'.'.$extension;

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.'.$extension,
            'stored_name' => $storedName,
            'mime_type' => $mimeTypes[$extension],
            'path' => $storedName,
        ]);
    }

    public function pdf(): static
    {
        $storedName = Str::uuid().'.pdf';

        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.pdf',
            'stored_name' => $storedName,
            'mime_type' => 'application/pdf',
            'path' => $storedName,
        ]);
    }

    public function document(): static
    {
        $extension = fake()->randomElement(['docx', 'xlsx']);
        $storedName = Str::uuid().'.'.$extension;

        $mimeTypes = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $this->state(fn (array $attributes) => [
            'original_name' => fake()->word().'.'.$extension,
            'stored_name' => $storedName,
            'mime_type' => $mimeTypes[$extension],
            'path' => $storedName,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
