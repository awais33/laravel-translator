<?php

namespace App\Http\Requests\Translations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => 'sometimes|string|exists:locales,code',
            'key'    => 'sometimes|string|max:255',
            'value'  => 'sometimes|string',
            'group'  => 'nullable|string|max:100',
            'tags'   => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }
}
