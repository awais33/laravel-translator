<?php

namespace App\Services;

use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;
use App\Repositories\TranslationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class TranslationService
{
    public function __construct(
        private readonly TranslationRepository $repository
    ) {}

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): Translation
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Translation
    {
        $locale = Locale::where('code', $data['locale'])->firstOrFail();

        $this->ensureUnique($locale->id, $data['key']);

        $tagIds = $this->resolveTagIds($data['tags'] ?? []);

        $translation = $this->repository->create([
            'locale_id' => $locale->id,
            'key'       => $data['key'],
            'value'     => $data['value'],
            'group'     => $data['group'] ?? 'general',
        ], $tagIds);

        $this->flushExportCache($data['locale']);

        return $translation;
    }

    public function update(int $id, array $data): Translation
    {
        $translation = $this->repository->findById($id);
        $oldLocaleCode = $translation->locale->code;

        $updateData = array_filter([
            'key'   => $data['key'] ?? null,
            'value' => $data['value'] ?? null,
            'group' => $data['group'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($data['locale'])) {
            $locale = Locale::where('code', $data['locale'])->firstOrFail();
            $updateData['locale_id'] = $locale->id;
        }

        $tagIds = isset($data['tags']) ? $this->resolveTagIds($data['tags']) : null;

        $translation = $this->repository->update($translation, $updateData, $tagIds);

        $this->flushExportCache($oldLocaleCode);
        if (isset($data['locale']) && $data['locale'] !== $oldLocaleCode) {
            $this->flushExportCache($data['locale']);
        }

        return $translation;
    }

    public function delete(int $id): void
    {
        $translation = $this->repository->findById($id);
        $localeCode  = $translation->locale->code;

        $this->repository->delete($translation);
        $this->flushExportCache($localeCode);
    }

    public function export(string $localeCode): array
    {
        $cacheKey = "translations:export:{$localeCode}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($localeCode) {
            Locale::where('code', $localeCode)->firstOrFail();

            return $this->repository->exportByLocale($localeCode);
        });
    }

    private function resolveTagIds(array $tagNames): array
    {
        return collect($tagNames)
            ->map(fn ($name) => Tag::firstOrCreate(['name' => strtolower($name)])->id)
            ->toArray();
    }

    private function ensureUnique(int $localeId, string $key): void
    {
        $exists = Translation::where('locale_id', $localeId)->where('key', $key)->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'key' => ["Translation key '{$key}' already exists for this locale."],
            ]);
        }
    }

    private function flushExportCache(string $localeCode): void
    {
        Cache::forget("translations:export:{$localeCode}");
    }
}
