<?php

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\TranslationRepository;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Cache;

describe('TranslationService', function () {
    beforeEach(function () {
        $this->locale  = Locale::create(['code' => 'en', 'name' => 'English']);
        $this->service = new TranslationService(new TranslationRepository());
    });

    it('creates a translation with tags', function () {
        Tag::create(['name' => 'web']);

        $translation = $this->service->create([
            'locale' => 'en',
            'key'    => 'nav.home',
            'value'  => 'Home',
            'group'  => 'navigation',
            'tags'   => ['web'],
        ]);

        expect($translation->key)->toBe('nav.home')
            ->and($translation->tags)->toHaveCount(1)
            ->and($translation->tags->first()->name)->toBe('web');
    });

    it('throws validation exception on duplicate key', function () {
        Translation::factory()->forLocale($this->locale)->create(['key' => 'auth.login']);

        expect(fn () => $this->service->create([
            'locale' => 'en',
            'key'    => 'auth.login',
            'value'  => 'Login again',
        ]))->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('updates translation value and replaces tags', function () {
        $translation = Translation::factory()->forLocale($this->locale)->create(['value' => 'Old']);
        Tag::create(['name' => 'mobile']);

        $updated = $this->service->update($translation->id, [
            'value' => 'New value',
            'tags'  => ['mobile'],
        ]);

        expect($updated->value)->toBe('New value')
            ->and($updated->tags->pluck('name'))->toContain('mobile');
    });

    it('deletes a translation and flushes cache', function () {
        Cache::spy();

        $translation = Translation::factory()->forLocale($this->locale)->create();
        $this->service->delete($translation->id);

        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
        Cache::shouldHaveReceived('forget')->with("translations:export:en");
    });

    it('exports translations grouped by group', function () {
        Translation::factory()->forLocale($this->locale)->create(['key' => 'login', 'value' => 'Login', 'group' => 'auth']);
        Translation::factory()->forLocale($this->locale)->create(['key' => 'title', 'value' => 'Welcome', 'group' => 'general']);

        $export = $this->service->export('en');

        expect($export)->toHaveKey('auth')
            ->and($export['auth']['login'])->toBe('Login')
            ->and($export['general']['title'])->toBe('Welcome');
    });

    it('caches export results', function () {
        Cache::spy();

        Translation::factory()->forLocale($this->locale)->count(3)->create();
        $this->service->export('en');
        $this->service->export('en');

        Cache::shouldHaveReceived('remember')->with("translations:export:en", \Mockery::any(), \Mockery::any());
    });
});
