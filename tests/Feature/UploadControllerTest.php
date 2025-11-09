<?php

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public_uploads');
    Storage::fake('private_uploads');
});

it('can list uploads for authenticated user', function () {
    $user = User::factory()->create();
    Upload::factory()->count(3)->forUser($user)->create();
    Upload::factory()->count(2)->create();

    actingAs($user)
        ->getJson('/api/upload')
        ->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('can filter uploads by type', function () {
    $user = User::factory()->create();
    Upload::factory()->count(2)->image()->forUser($user)->create();
    Upload::factory()->count(1)->pdf()->forUser($user)->create();

    actingAs($user)
        ->getJson('/api/upload?type=images')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

it('can search uploads', function () {
    $user = User::factory()->create();
    Upload::factory()->forUser($user)->create(['title' => 'Important Document']);
    Upload::factory()->forUser($user)->create(['title' => 'Random File']);

    actingAs($user)
        ->getJson('/api/upload?search=Important')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Important Document');
});

it('can upload a public file', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('test.jpg');

    $response = actingAs($user)
        ->postJson('/api/upload', [
            'file' => $file,
            'title' => 'Test Upload',
            'description' => 'Test description',
            'is_private' => false,
        ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'slug',
                'title',
                'original_name',
                'mime_type',
                'size',
                'human_readable_size',
                'is_private',
                'url',
                'download_url',
            ],
        ]);

    expect($response->json('data.title'))->toBe('Test Upload');
    expect($response->json('data.is_private'))->toBeFalse();

    Storage::disk('public_uploads')->assertExists($response->json('data.path'));
});

it('can upload a private file', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('private.jpg');

    $response = actingAs($user)
        ->postJson('/api/upload', [
            'file' => $file,
            'title' => 'Private Upload',
            'is_private' => true,
        ])
        ->assertSuccessful();

    expect($response->json('data.is_private'))->toBeTrue();
});

it('validates required file on upload', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/upload', [
            'title' => 'Test',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('validates file size on upload', function () {
    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('large.pdf', 20000);

    actingAs($user)
        ->postJson('/api/upload', [
            'file' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});

it('can show an upload', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->forUser($user)->create();

    actingAs($user)
        ->getJson("/api/upload/{$upload->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $upload->id)
        ->assertJsonPath('data.title', $upload->title);
});

it('can view public uploads from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $upload = Upload::factory()->public()->forUser($otherUser)->create();

    actingAs($user)
        ->getJson("/api/upload/{$upload->id}")
        ->assertSuccessful();
});

it('cannot view private uploads from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $upload = Upload::factory()->private()->forUser($otherUser)->create();

    actingAs($user)
        ->getJson("/api/upload/{$upload->id}")
        ->assertForbidden();
});

it('can update own upload', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->forUser($user)->create();

    actingAs($user)
        ->putJson("/api/upload/{$upload->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Updated Title');

    expect($upload->fresh()->title)->toBe('Updated Title');
});

it('cannot update other users uploads', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $upload = Upload::factory()->forUser($otherUser)->create();

    actingAs($user)
        ->putJson("/api/upload/{$upload->id}", [
            'title' => 'Hacked',
        ])
        ->assertForbidden();
});

it('can download own upload', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->forUser($user)->create();

    Storage::disk($upload->disk)->put($upload->path, 'test content');

    actingAs($user)
        ->get("/api/upload/{$upload->slug}/download")
        ->assertSuccessful()
        ->assertDownload($upload->original_name);
});

it('can download public uploads from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $upload = Upload::factory()->public()->forUser($otherUser)->create();

    Storage::disk($upload->disk)->put($upload->path, 'test content');

    actingAs($user)
        ->get("/api/upload/{$upload->slug}/download")
        ->assertSuccessful();
});

it('cannot download private uploads from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $upload = Upload::factory()->private()->forUser($otherUser)->create();

    actingAs($user)
        ->get("/api/upload/{$upload->slug}/download")
        ->assertForbidden();
});

it('can delete own upload', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->forUser($user)->create();

    Storage::disk($upload->disk)->put($upload->path, 'test content');

    actingAs($user)
        ->deleteJson("/api/upload/{$upload->id}")
        ->assertSuccessful();

    expect(Upload::find($upload->id))->toBeNull();
    Storage::disk($upload->disk)->assertMissing($upload->path);
});

it('cannot delete other users uploads', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $upload = Upload::factory()->forUser($otherUser)->create();

    actingAs($user)
        ->deleteJson("/api/upload/{$upload->id}")
        ->assertForbidden();

    expect(Upload::find($upload->id))->not->toBeNull();
});

it('requires authentication for all endpoints', function () {
    $upload = Upload::factory()->create();

    $this->getJson('/api/upload')->assertUnauthorized();
    $this->postJson('/api/upload')->assertUnauthorized();
    $this->getJson("/api/upload/{$upload->id}")->assertUnauthorized();
    $this->putJson("/api/upload/{$upload->id}")->assertUnauthorized();
    $this->deleteJson("/api/upload/{$upload->id}")->assertUnauthorized();
    $this->getJson("/api/upload/{$upload->slug}/download")->assertUnauthorized();
});

it('can get storage statistics', function () {
    $user = User::factory()->create();
    Upload::factory()->count(5)->image()->forUser($user)->create(['size' => 1024]);
    Upload::factory()->count(3)->pdf()->forUser($user)->create(['size' => 2048]);

    $response = actingAs($user)
        ->getJson('/api/upload/statistics')
        ->assertSuccessful();

    expect($response->json('data.total_files'))->toBe(8);
    expect($response->json('data.images_count'))->toBe(5);
    expect($response->json('data.documents_count'))->toBe(3);
});

it('can bulk delete uploads', function () {
    $user = User::factory()->create();
    $uploads = Upload::factory()->count(3)->forUser($user)->create();

    $response = actingAs($user)
        ->postJson('/api/upload/bulk-delete', [
            'upload_ids' => $uploads->pluck('id')->toArray(),
        ])
        ->assertSuccessful();

    expect($response->json('deleted_count'))->toBe(3);
    expect(Upload::whereIn('id', $uploads->pluck('id'))->count())->toBe(0);
});

it('cannot bulk delete other users uploads', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $uploads = Upload::factory()->count(3)->forUser($otherUser)->create();

    $response = actingAs($user)
        ->postJson('/api/upload/bulk-delete', [
            'upload_ids' => $uploads->pluck('id')->toArray(),
        ])
        ->assertSuccessful();

    expect($response->json('deleted_count'))->toBe(0);
    expect(Upload::whereIn('id', $uploads->pluck('id'))->count())->toBe(3);
});

it('can bulk download uploads as zip', function () {
    if (! class_exists('ZipArchive')) {
        $this->markTestSkipped('ZipArchive extension is not installed.');
    }

    $user = User::factory()->create();
    $uploads = Upload::factory()->count(2)->forUser($user)->create();

    foreach ($uploads as $upload) {
        Storage::disk($upload->disk)->put($upload->path, 'test content');
    }

    actingAs($user)
        ->postJson('/api/upload/bulk-download', [
            'upload_ids' => $uploads->pluck('id')->toArray(),
        ])
        ->assertSuccessful()
        ->assertDownload();
});

it('validates bulk delete request', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/upload/bulk-delete', [
            'upload_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_ids']);
});

it('validates bulk download request', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->postJson('/api/upload/bulk-download', [
            'upload_ids' => [],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_ids']);
});
