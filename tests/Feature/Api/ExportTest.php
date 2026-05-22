<?php

use App\Models\Locale;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->locale = Locale::create(['code' => 'en', 'name' => 'English']);
});

describe('JSON Export', function () {
    it('exports translations grouped by group for a locale', function () {
        Translation::factory()->forLocale($this->locale)->create(['key' => 'login',   'value' => 'Login',   'group' => 'auth']);
        Translation::factory()->forLocale($this->locale)->create(['key' => 'welcome', 'value' => 'Welcome', 'group' => 'general']);

        $this->getJson('/api/export/en')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.auth.login', 'Login')
            ->assertJsonPath('data.data.general.welcome', 'Welcome');
    });

    it('returns 404 for unknown locale', function () {
        $this->getJson('/api/export/xx')->assertNotFound();
    });

    it('does not require authentication', function () {
        $this->getJson('/api/export/en')->assertOk();
    });

    it('returns fresh data after a translation is updated', function () {
        $translation = Translation::factory()->forLocale($this->locale)->create([
            'key' => 'title', 'value' => 'Old Title', 'group' => 'general',
        ]);

        $this->getJson('/api/export/en')
            ->assertJsonPath('data.data.general.title', 'Old Title');

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("/api/translations/{$translation->id}", ['value' => 'New Title']);

        $this->getJson('/api/export/en')
            ->assertJsonPath('data.data.general.title', 'New Title');
    });

    it('responds within 500ms for a reasonable dataset', function () {
        $rows = array_map(fn ($i) => [
            'locale_id'  => $this->locale->id,
            'key'        => "key.{$i}",
            'value'      => "value {$i}",
            'group'      => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ], range(1, 500));

        \Illuminate\Support\Facades\DB::table('translations')->insert($rows);

        $start = microtime(true);
        $this->getJson('/api/export/en')->assertOk();

        expect((microtime(true) - $start) * 1000)->toBeLessThan(500);
    });
});
