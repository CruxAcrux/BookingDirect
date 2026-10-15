<?php

use App\Http\Controllers\Api\V1\Barbers\PortfolioController;
use App\Models\Barber;
use App\Models\PortfolioImage;
use App\Models\Shop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->shop = Shop::factory()->create();
    [$this->user, $this->barber] = $this->barberWithLogin($this->shop);
});

it('stores an upload with a square thumbnail', function () {
    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/barbers/{$this->barber->id}/portfolio", [
            'image' => UploadedFile::fake()->image('fade.jpg', 3200, 2400),
            'caption' => 'Low fade',
        ])
        ->assertCreated()
        ->assertJsonPath('data.caption', 'Low fade');

    $image = PortfolioImage::findOrFail($response->json('data.id'));
    $full = getimagesizefromstring(Storage::disk('public')->get($image->path));
    $thumb = getimagesizefromstring(Storage::disk('public')->get($image->thumbnail_path));

    expect([$full[0], $full[1]])->toBe([1600, 1200])
        ->and([$thumb[0], $thumb[1]])->toBe([600, 600])
        ->and($full['mime'])->toBe('image/webp');
});

it('updates captions and deletes images with their files', function () {
    $id = $this->actingAs($this->user)
        ->postJson("/api/v1/barbers/{$this->barber->id}/portfolio", ['image' => UploadedFile::fake()->image('a.jpg', 800, 800)])
        ->json('data.id');
    $image = PortfolioImage::findOrFail($id);

    $this->actingAs($this->user)
        ->patchJson("/api/v1/barbers/{$this->barber->id}/portfolio/{$id}", ['caption' => 'Taper'])
        ->assertOk()
        ->assertJsonPath('data.caption', 'Taper');

    $this->actingAs($this->user)
        ->deleteJson("/api/v1/barbers/{$this->barber->id}/portfolio/{$id}")
        ->assertNoContent();

    Storage::disk('public')->assertMissing([$image->path, $image->thumbnail_path]);
});

it('limits the portfolio size', function () {
    PortfolioImage::factory()->count(PortfolioController::MAX_IMAGES)->for($this->barber)->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->user)
        ->postJson("/api/v1/barbers/{$this->barber->id}/portfolio", ['image' => UploadedFile::fake()->image('a.jpg')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});

it('does not let barbers change a colleague\'s portfolio', function () {
    $colleague = Barber::factory()->for($this->shop)->create();

    $this->actingAs($this->user)
        ->postJson("/api/v1/barbers/{$colleague->id}/portfolio", ['image' => UploadedFile::fake()->image('a.jpg')])
        ->assertForbidden();
});

it('reorders the portfolio', function () {
    [$a, $b] = PortfolioImage::factory()->count(2)->for($this->barber)->sequence(['sort_order' => 0], ['sort_order' => 1])->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->user)
        ->putJson("/api/v1/barbers/{$this->barber->id}/portfolio/order", ['ids' => [$b->id, $a->id]])
        ->assertOk()
        ->assertJsonPath('data.0.id', $b->id);
});
