<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Locales\StoreLocaleRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Services\LocaleService;
use Illuminate\Http\JsonResponse;

class LocaleController extends Controller
{
    public function __construct(
        private readonly LocaleService $service
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->service->all());
    }

    public function store(StoreLocaleRequest $request): JsonResponse
    {
        $locale = $this->service->create($request->validated());

        return ApiResponse::created($locale, 'Locale created successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->deactivate($id);

        return ApiResponse::success(null, 'Locale deactivated successfully');
    }
}
