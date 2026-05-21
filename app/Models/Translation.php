<?php

namespace App\Models;

use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Translation extends Model
{
    /** @use HasFactory<TranslationFactory> */
    use HasFactory;

    protected $fillable = ['locale_id', 'key', 'value', 'group'];

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'translation_tag');
    }

    public function scopeForLocale(Builder $query, string $localeCode): Builder
    {
        return $query->whereHas('locale', fn ($q) => $q->where('code', $localeCode));
    }

    public function scopeWithTag(Builder $query, string|array $tags): Builder
    {
        return $query->whereHas('tags', function ($q) use ($tags) {
            $q->whereIn('name', (array) $tags);
        });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $lower = strtolower($term);

        return $query->where(function ($q) use ($lower) {
            $q->whereRaw('LOWER(key) LIKE ?', ["%{$lower}%"])
              ->orWhereRaw('LOWER(value) LIKE ?', ["%{$lower}%"]);
        });
    }
}
