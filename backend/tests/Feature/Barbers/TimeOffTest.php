<?php

use App\Models\Barber;
use App\Models\Shop;
use App\Models\TimeOff;

beforeEach(function () {
    $this->shop = Shop::factory()->create(['timezone' => 'Europe/Stockholm']);
    $this->owner = $this->staffMember($this->shop);
});

it('stores local shop time as UTC on both sides of daylight saving time', function (string $local, string $utc) {
    $barber = Barber::factory()->for($this->shop)->create();

    $this->actingAs($this->owner)
        ->postJson("/api/v1/barbers/{$barber->id}/time-off", [
            'starts_at' => $local,
            'ends_at' => substr($local, 0, 11).'17:00',
            'reason' => 'other',
        ])
        ->assertCreated()
        ->assertJsonPath('data.starts_at', $utc)
        ->assertJsonPath('data.starts_at_local', $local)
        ->assertJsonPath('data.all_day', false);
})->with([
    'summer (UTC+2)' => ['2027-07-01T09:00', '2027-07-01T07:00:00Z'],
    'winter (UTC+1)' => ['2027-11-03T09:00', '2027-11-03T08:00:00Z'],
]);

it('recognizes full days off', function () {
    $barber = Barber::factory()->for($this->shop)->create();

    $this->actingAs($this->owner)
        ->postJson("/api/v1/barbers/{$barber->id}/time-off", [
            'starts_at' => '2027-07-12T00:00',
            'ends_at' => '2027-07-19T00:00',
            'reason' => 'vacation',
        ])
        ->assertCreated()
        ->assertJsonPath('data.all_day', true)
        ->assertJsonPath('data.reason', 'vacation');
});

it('rejects time off that ends before it starts', function () {
    $barber = Barber::factory()->for($this->shop)->create();

    $this->actingAs($this->owner)
        ->postJson("/api/v1/barbers/{$barber->id}/time-off", [
            'starts_at' => '2027-07-12T12:00', 'ends_at' => '2027-07-12T09:00', 'reason' => 'sick',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ends_at');
});

it('lets barbers manage only their own time off', function () {
    [$user, $barber] = $this->barberWithLogin($this->shop);
    $colleague = Barber::factory()->for($this->shop)->create();
    $payload = ['starts_at' => '2027-07-12T00:00', 'ends_at' => '2027-07-13T00:00', 'reason' => 'sick'];

    $this->actingAs($user)->postJson("/api/v1/barbers/{$barber->id}/time-off", $payload)->assertCreated();
    $this->actingAs($user)->postJson("/api/v1/barbers/{$colleague->id}/time-off", $payload)->assertForbidden();
});

it('updates and removes time off', function () {
    $barber = Barber::factory()->for($this->shop)->create();
    $timeOff = TimeOff::factory()->for($barber)->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->owner)
        ->patchJson("/api/v1/barbers/{$barber->id}/time-off/{$timeOff->id}", ['note' => 'Dentist'])
        ->assertOk()
        ->assertJsonPath('data.note', 'Dentist');

    $this->actingAs($this->owner)
        ->deleteJson("/api/v1/barbers/{$barber->id}/time-off/{$timeOff->id}")
        ->assertNoContent();

    expect(TimeOff::find($timeOff->id))->toBeNull();
});

it('does not resolve time off through another barber\'s URL', function () {
    [$a, $b] = Barber::factory()->for($this->shop)->count(2)->create();
    $timeOff = TimeOff::factory()->for($a)->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->owner)
        ->deleteJson("/api/v1/barbers/{$b->id}/time-off/{$timeOff->id}")
        ->assertNotFound();
});
