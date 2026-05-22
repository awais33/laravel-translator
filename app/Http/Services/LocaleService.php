<?php

namespace App\Http\Services;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Collection;

class LocaleService
{
    public function all(): Collection
    {
        return Locale::where('is_active', true)->get();
    }

    public function create(array $data): Locale
    {
        return Locale::create($data);
    }

    public function deactivate(int $id): Locale
    {
        $locale = Locale::findOrFail($id);
        $locale->update(['is_active' => false]);

        return $locale;
    }
}
