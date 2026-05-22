<?php

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;

beforeEach(function () {
    $this->user   = User::factory()->create();
    $this->locale = Locale::create(['code' => 'en', 'name' => 'English']);
    $this->tag    = Tag::create(['name' => 'web']);
});

describe('Translation CRUD', function () {
    it('lists translations with pagination', function () {
        Translation::factory()->forLocale($this->locale)->count(5)->create();

        $this->actingAs($this->user)->getJson('/api/translations')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'total', 'per_page'],
                'links',
            ]);
    });

    it('creates a translation', function () {
        $this->actingAs($this->user)->postJson('/api/translations', [
            'locale' => 'en',
            'key'    => 'welcome.title',
            'value'  => 'Welcome to our app',
            'group'  => 'general',
            'tags'   => ['web', 'mobile'],
        ])->assertStatus(201)
          ->assertJsonPath('success', true)
          ->assertJsonPath('data.key', 'welcome.title')
          ->assertJsonPath('data.value', 'Welcome to our app');

        $this->assertDatabaseHas('translations', ['key' => 'welcome.title']);
    });

    it('returns 422 for non-existent locale on create', function () {
        $this->actingAs($this->user)->postJson('/api/translations', [
            'locale' => 'xx',
            'key'    => 'some.key',
            'value'  => 'Some value',
        ])->assertStatus(422);
    });

    it('prevents duplicate keys per locale', function () {
        Translation::factory()->forLocale($this->locale)->create(['key' => 'auth.login']);

        $this->actingAs($this->user)->postJson('/api/translations', [
            'locale' => 'en',
            'key'    => 'auth.login',
            'value'  => 'Login',
        ])->assertStatus(422);
    });

    it('shows a single translation', function () {
        $translation = Translation::factory()->forLocale($this->locale)->create();
        $translation->tags()->attach($this->tag);

        $this->actingAs($this->user)->getJson("/api/translations/{$translation->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $translation->id)
            ->assertJsonPath('data.tags.0', 'web');
    });

    it('updates a translation', function () {
        $translation = Translation::factory()->forLocale($this->locale)->create(['value' => 'Old value']);

        $this->actingAs($this->user)->putJson("/api/translations/{$translation->id}", [
            'value' => 'Updated value',
            'tags'  => ['mobile'],
        ])->assertOk()
          ->assertJsonPath('success', true)
          ->assertJsonPath('data.value', 'Updated value');

        $this->assertDatabaseHas('translations', ['id' => $translation->id, 'value' => 'Updated value']);
    });

    it('deletes a translation', function () {
        $translation = Translation::factory()->forLocale($this->locale)->create();

        $this->actingAs($this->user)->deleteJson("/api/translations/{$translation->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    });

    it('returns 404 for missing translation', function () {
        $this->actingAs($this->user)->getJson('/api/translations/999999')
            ->assertNotFound();
    });
});

describe('Translation Search & Filter', function () {
    it('filters translations by locale', function () {
        $fr = Locale::create(['code' => 'fr', 'name' => 'French']);
        Translation::factory()->forLocale($this->locale)->count(3)->create();
        Translation::factory()->forLocale($fr)->count(2)->create();

        $this->actingAs($this->user)->getJson('/api/translations?locale=en')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('filters translations by tag', function () {
        $t1 = Translation::factory()->forLocale($this->locale)->create();
        Translation::factory()->forLocale($this->locale)->create();
        $t1->tags()->attach($this->tag);

        $this->actingAs($this->user)->getJson('/api/translations?tag=web')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('searches translations by key or content', function () {
        Translation::factory()->forLocale($this->locale)->create(['key' => 'login.button', 'value' => 'Sign in']);
        Translation::factory()->forLocale($this->locale)->create(['key' => 'logout.button', 'value' => 'Log out']);
        Translation::factory()->forLocale($this->locale)->create(['key' => 'home.title', 'value' => 'Welcome home']);

        $this->actingAs($this->user)->getJson('/api/translations?search=button')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters by exact key', function () {
        Translation::factory()->forLocale($this->locale)->create(['key' => 'auth.login']);
        Translation::factory()->forLocale($this->locale)->create(['key' => 'auth.logout']);

        $this->actingAs($this->user)->getJson('/api/translations?key=auth.login')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('respects per_page and sort_direction from ApiRequest', function () {
        Translation::factory()->forLocale($this->locale)->count(10)->create();

        $this->actingAs($this->user)->getJson('/api/translations?per_page=3&sort_direction=asc')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonCount(3, 'data');
    });
});
