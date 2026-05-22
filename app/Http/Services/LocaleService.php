<?php

namespace App\Http\Services;

use App\Models\Locale;
use App\Repositories\LocaleRepository;
use Illuminate\Database\Eloquent\Collection;

class LocaleService
{
    public function __construct(
        private readonly LocaleRepository $localeRepository
    ) {}

    public function all(): Collection
    {
        return $this->localeRepository->allActive();
    }

    public function create(array $data): Locale
    {
        return $this->localeRepository->create($data);
    }

    public function deactivate(int $id): Locale
    {
        $locale = $this->localeRepository->findOrFail($id);

        return $this->localeRepository->update($locale, ['is_active' => false]);
    }
}
