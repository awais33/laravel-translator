<?php

namespace App\Http\Services;

use App\Http\Requests\Translations\ListTranslationsRequest;
use App\Models\Translation;
use App\Repositories\LocaleRepository;
use App\Repositories\TagRepository;
use App\Repositories\TranslationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class TranslationService
{
    public function __construct(
        private readonly TranslationRepository $translationRepository,
        private readonly LocaleRepository      $localeRepository,
        private readonly TagRepository         $tagRepository,
    ) {}

    public function list(ListTranslationsRequest $request): LengthAwarePaginator
    {
        return $this->translationRepository->paginate($request);
    }

    public function find(int $id): Translation
    {
        return $this->translationRepository->findById($id);
    }

    public function create(array $data): Translation
    {
        $locale = $this->localeRepository->findByCodeOrFail($data['locale']);

        $this->ensureUniqueKey($locale->id, $data['key']);

        $tagIds      = $this->resolveTagIds($data['tags'] ?? []);
        $translation = $this->translationRepository->create([
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
        $translation   = $this->translationRepository->findById($id);
        $oldLocaleCode = $translation->locale->code;

        $updateData = array_filter([
            'key'   => $data['key']   ?? null,
            'value' => $data['value'] ?? null,
            'group' => $data['group'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($data['locale'])) {
            $locale                  = $this->localeRepository->findByCodeOrFail($data['locale']);
            $updateData['locale_id'] = $locale->id;
        }

        $tagIds      = isset($data['tags']) ? $this->resolveTagIds($data['tags']) : null;
        $translation = $this->translationRepository->update($translation, $updateData, $tagIds);

        $this->flushExportCache($oldLocaleCode);

        if (isset($data['locale']) && $data['locale'] !== $oldLocaleCode) {
            $this->flushExportCache($data['locale']);
        }

        return $translation;
    }

    public function delete(int $id): void
    {
        $translation = $this->translationRepository->findById($id);
        $localeCode  = $translation->locale->code;

        $this->translationRepository->delete($translation);
        $this->flushExportCache($localeCode);
    }

    public function export(string $localeCode): array
    {
        return Cache::remember("translations:export:{$localeCode}", now()->addHours(24), function () use ($localeCode) {
            $this->localeRepository->findByCodeOrFail($localeCode);

            return $this->translationRepository->exportByLocale($localeCode);
        });
    }

    private function resolveTagIds(array $tagNames): array
    {
        return collect($tagNames)
            ->map(fn ($name) => $this->tagRepository->firstOrCreateByName($name)->id)
            ->toArray();
    }

    private function ensureUniqueKey(int $localeId, string $key): void
    {
        if ($this->translationRepository->existsByLocaleAndKey($localeId, $key)) {
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
