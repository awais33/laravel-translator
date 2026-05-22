<?php

use App\Models\Locale;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Locale Management', function () {
    it('lists active locales', function () {
        Locale::create(['code' => 'en', 'name' => 'English']);
        Locale::create(['code' => 'fr', 'name' => 'French', 'is_active' => false]);

        $this->actingAs($this->user)->getJson('/api/locales')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    });

    it('creates a new locale', function () {
        $this->actingAs($this->user)->postJson('/api/locales', [
            'code' => 'de',
            'name' => 'German',
        ])->assertStatus(201)
          ->assertJsonPath('success', true)
          ->assertJsonPath('data.code', 'de');

        $this->assertDatabaseHas('locales', ['code' => 'de']);
    });

    it('rejects duplicate locale codes', function () {
        Locale::create(['code' => 'en', 'name' => 'English']);

        $this->actingAs($this->user)->postJson('/api/locales', [
            'code' => 'en',
            'name' => 'English Again',
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);
    });

    it('deactivates a locale', function () {
        $locale = Locale::create(['code' => 'es', 'name' => 'Spanish']);

        $this->actingAs($this->user)->deleteJson("/api/locales/{$locale->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('locales', ['id' => $locale->id, 'is_active' => false]);
    });
});
