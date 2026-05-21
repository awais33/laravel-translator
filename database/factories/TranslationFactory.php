<?php

namespace Database\Factories;

use App\Models\Locale;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Translation> */
class TranslationFactory extends Factory
{
    private static array $groups = ['general', 'auth', 'validation', 'navigation', 'emails', 'errors', 'dashboard'];

    public function definition(): array
    {
        return [
            'locale_id' => Locale::factory(),
            'key'       => $this->faker->unique()->slug(3),
            'value'     => $this->faker->sentence(),
            'group'     => $this->faker->randomElement(self::$groups),
        ];
    }

    public function forLocale(Locale $locale): static
    {
        return $this->state(['locale_id' => $locale->id]);
    }
}
