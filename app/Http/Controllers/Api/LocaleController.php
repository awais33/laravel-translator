<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Locale::where('is_active', true)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:locales,code',
            'name' => 'required|string|max:100',
        ]);

        $locale = Locale::create($data);

        return response()->json($locale, 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $locale = Locale::findOrFail($id);
        $locale->update(['is_active' => false]);

        return response()->json(['message' => 'Locale deactivated.']);
    }
}
