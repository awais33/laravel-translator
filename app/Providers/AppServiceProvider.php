<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Keep the Swagger UI in sync with our hand-crafted openapi.yaml
        $source = base_path('openapi.yaml');
        $dest   = storage_path('api-docs/api-docs.yaml');

        if (File::exists($source) && (!File::exists($dest) || File::lastModified($source) > File::lastModified($dest))) {
            File::ensureDirectoryExists(storage_path('api-docs'));
            File::copy($source, $dest);
        }
    }
}
