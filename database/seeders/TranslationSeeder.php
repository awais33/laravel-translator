<?php

namespace Database\Seeders;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TranslationSeeder extends Seeder
{
    private const BATCH_SIZE = 500;
    private const TOTAL = 100000;

    public function run(): void
    {
        $this->command->info('Seeding 100,000+ translations...');

        $localeIds = Locale::pluck('id')->toArray();
        $tagIds    = Tag::pluck('id')->toArray();

        if (empty($localeIds)) {
            $this->command->error('No locales found. Run LocaleSeeder first.');
            return;
        }

        $groups    = ['general', 'auth', 'validation', 'navigation', 'emails', 'errors', 'dashboard'];
        $words     = ['button', 'label', 'title', 'message', 'error', 'success', 'info', 'warning', 'hint', 'placeholder'];
        $inserted  = 0;
        $keyIndex  = 1;

        DB::disableQueryLog();

        while ($inserted < self::TOTAL) {
            $batch = [];

            for ($i = 0; $i < self::BATCH_SIZE && $inserted < self::TOTAL; $i++, $inserted++, $keyIndex++) {
                $localeId = $localeIds[array_rand($localeIds)];
                $word     = $words[array_rand($words)];
                $group    = $groups[array_rand($groups)];

                $batch[] = [
                    'locale_id'  => $localeId,
                    'key'        => "{$word}.{$group}.{$keyIndex}",
                    'value'      => fake()->sentence(),
                    'group'      => $group,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('translations')->insertOrIgnore($batch);

            if ($inserted % 10000 === 0) {
                $this->command->info("  {$inserted} records inserted...");
            }
        }

        $this->command->info('Attaching tags to translations...');

        $translationIds = Translation::pluck('id')->toArray();

        $pivotBatch = [];
        $sampleSize = min(30000, count($translationIds));
        $sample     = array_rand(array_flip($translationIds), $sampleSize);

        foreach ((array) $sample as $translationId) {
            $selectedTags = (array) array_rand(array_flip($tagIds), rand(1, 3));
            foreach ($selectedTags as $tagId) {
                $pivotBatch[] = ['translation_id' => $translationId, 'tag_id' => $tagId];
            }

            if (count($pivotBatch) >= 2000) {
                DB::table('translation_tag')->insertOrIgnore($pivotBatch);
                $pivotBatch = [];
            }
        }

        if (! empty($pivotBatch)) {
            DB::table('translation_tag')->insertOrIgnore($pivotBatch);
        }

        $this->command->info('Done! ' . Translation::count() . ' translations seeded.');
    }
}
