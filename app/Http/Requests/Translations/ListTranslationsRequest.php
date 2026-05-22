<?php

namespace App\Http\Requests\Translations;

use App\Http\Requests\ApiRequest;

class ListTranslationsRequest extends ApiRequest
{
    protected string $defaultSortBy    = 'created_at';
    protected array $allowedSortFields = ['id', 'key', 'group', 'created_at', 'updated_at'];

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'locale' => 'nullable|string|exists:locales,code',
            'tag'    => 'nullable|string|max:50',
            'search' => 'nullable|string|max:255',
            'key'    => 'nullable|string|max:255',
            'group'  => 'nullable|string|max:100',
        ]);
    }

    public function filters(): array
    {
        return array_filter($this->only(['locale', 'tag', 'search', 'key', 'group']), fn ($v) => $v !== null);
    }
}
