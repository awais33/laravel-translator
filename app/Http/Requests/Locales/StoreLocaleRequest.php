<?php

namespace App\Http\Requests\Locales;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:10|unique:locales,code',
            'name' => 'required|string|max:100',
        ];
    }
}
