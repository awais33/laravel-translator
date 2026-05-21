<?php

use App\Models\Locale;
use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->locale = Locale::create(['code' => 'en', 'name' => 'English']);
});

describe('JSON Export', function () {
    it('exports translations grouped by group for a locale', function () {
        Translation::factory()->forLocale($this->locale)->create([
            'key'   => 'login',
            'value' => 'Login',
            'group' => 'auth',
        ]);
        Translation::factory()->forLocale($this->locale)->create([
            'key'   => 'welcome',
            'value' => 'Welcome',
            'group' => 'general',
        ]);

        $response = $this->getJson('/api/export/en');

        $response->assertOk()
            ->assertJsonPath('locale', 'en')
            ->assertJsonPath('data.auth.login', 'Login')
            ->assertJsonPath('data.general.welcome', 'Welcome');
    });

    it('returns 404 for unknown locale', function () {
        $this->getJson('/api/export/xx')->assertNotFound();
    });

    it('caches the export response', function () {
        Translation::factory()->forLocale($this->locale)->create([
            'key' => 'btn', 'value' => 'Click', 'group' => 'ui',
        ]);

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['ui' => ['btn' => 'Click']]);

        $this->getJson('/api/export/en')->assertOk();
    });

    it('does not require authentication for export', function () {
        $this->getJson('/api/export/en')->assertOk();
    });

    it('returns fresh data after a translation is updated', function () {
        $translation = Translation::factory()->forLocale($this->locale)->create([
            'key' => 'title', 'value' => 'Old Title', 'group' => 'general',
        ]);

        $this->getJson('/api/export/en')->assertJsonPath('data.general.title', 'Old Title');

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user)->putJson("/api/translations/{$translation->id}", [
            'value' => 'New Title',
        ]);

        $this->getJson('/api/export/en')->assertJsonPath('data.general.title', 'New Title');
    });

    it('responds within 500ms for large datasets', function () {
        $translations = array_map(fn ($i) => [
            'locale_id'  => $this->locale->id,
            'key'        => "key.{$i}",
            'value'      => "value {$i}",
            'group'      => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ], range(1, 500));

        \Illuminate\Support\Facades\DB::table('translations')->insert($translations);

        $start = microtime(true);
        $this->getJson('/api/export/en')->assertOk();
        $elapsed = (microtime(true) - $start) * 1000;

        expect($elapsed)->toBeLessThan(500);
    });
});
