<?php

namespace App\Console\Commands;

use Database\Seeders\LocaleSeeder;
use Database\Seeders\TagSeeder;
use Database\Seeders\TranslationSeeder;
use Illuminate\Console\Command;

class SeedTranslations extends Command
{
    protected $signature = 'translations:seed {--count=100000 : Number of records to generate}';

    protected $description = 'Seed the database with a large number of translations for scalability testing';

    public function handle(): int
    {
        $this->info('Running locale and tag seeders first...');

        (new LocaleSeeder())->run();
        (new TagSeeder())->run();

        $seeder          = new TranslationSeeder();
        $seeder->command = $this;
        $seeder->run();

        return self::SUCCESS;
    }
}
