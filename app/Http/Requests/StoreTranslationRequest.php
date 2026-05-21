<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => 'required|string|exists:locales,code',
            'key'    => 'required|string|max:255',
            'value'  => 'required|string',
            'group'  => 'nullable|string|max:100',
            'tags'   => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }
}
