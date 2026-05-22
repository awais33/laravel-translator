<?php

namespace App\Repositories;

use App\Models\Locale;
use Illuminate\Database\Eloquent\Collection;

class LocaleRepository
{
    public function allActive(): Collection
    {
        return Locale::where('is_active', true)->get();
    }

    public function findByCode(string $code): ?Locale
    {
        return Locale::where('code', $code)->first();
    }

    public function findByCodeOrFail(string $code): Locale
    {
        return Locale::where('code', $code)->firstOrFail();
    }

    public function findOrFail(int $id): Locale
    {
        return Locale::findOrFail($id);
    }

    public function create(array $data): Locale
    {
        return Locale::create($data);
    }

    public function update(Locale $locale, array $data): Locale
    {
        $locale->update($data);

        return $locale->fresh();
    }
}
