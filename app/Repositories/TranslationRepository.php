<?php

namespace App\Repositories;

use App\Http\Requests\Translations\ListTranslationsRequest;
use App\Models\Translation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TranslationRepository
{
    public function paginate(ListTranslationsRequest $request): LengthAwarePaginator
    {
        $query   = Translation::with(['locale', 'tags']);
        $filters = $request->filters();

        if (!empty($filters['locale'])) {
            $query->forLocale($filters['locale']);
        }

        if (!empty($filters['tag'])) {
            $query->withTag($filters['tag']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['key'])) {
            $query->where('key', $filters['key']);
        }

        if (!empty($filters['group'])) {
            $query->where('group', $filters['group']);
        }

        $request->applyOrdering($query);

        return $query->paginate($request->getPerPage(), ['*'], 'page', $request->getPage());
    }

    public function findById(int $id): Translation
    {
        return Translation::with(['locale', 'tags'])->findOrFail($id);
    }

    public function create(array $data, array $tagIds = []): Translation
    {
        $translation = Translation::create($data);

        if (!empty($tagIds)) {
            $translation->tags()->sync($tagIds);
        }

        return $translation->load(['locale', 'tags']);
    }

    public function update(Translation $translation, array $data, ?array $tagIds = null): Translation
    {
        $translation->update($data);

        if ($tagIds !== null) {
            $translation->tags()->sync($tagIds);
        }

        return $translation->load(['locale', 'tags']);
    }

    public function delete(Translation $translation): void
    {
        $translation->delete();
    }

    public function existsByLocaleAndKey(int $localeId, string $key): bool
    {
        return Translation::where('locale_id', $localeId)->where('key', $key)->exists();
    }

    public function exportByLocale(string $localeCode): array
    {
        return Translation::forLocale($localeCode)
            ->select(['key', 'value', 'group'])
            ->get()
            ->groupBy('group')
            ->map(fn ($items) => $items->pluck('value', 'key'))
            ->toArray();
    }
}
