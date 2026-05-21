<?php

namespace Database\Factories;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Locale> */
class LocaleFactory extends Factory
{
    private static array $locales = [
        ['code' => 'en', 'name' => 'English'],
        ['code' => 'fr', 'name' => 'French'],
        ['code' => 'es', 'name' => 'Spanish'],
        ['code' => 'de', 'name' => 'German'],
        ['code' => 'it', 'name' => 'Italian'],
        ['code' => 'pt', 'name' => 'Portuguese'],
        ['code' => 'nl', 'name' => 'Dutch'],
        ['code' => 'ar', 'name' => 'Arabic'],
        ['code' => 'zh', 'name' => 'Chinese'],
        ['code' => 'ja', 'name' => 'Japanese'],
    ];

    public function definition(): array
    {
        $locale = $this->faker->unique()->randomElement(self::$locales);

        return [
            'code'      => $locale['code'],
            'name'      => $locale['name'],
            'is_active' => true,
        ];
    }
}
