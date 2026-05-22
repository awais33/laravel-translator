<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Services\TranslationService;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly TranslationService $service
    ) {}

    public function __invoke(string $locale): JsonResponse
    {
        $data = $this->service->export($locale);

        return ApiResponse::success([
            'locale' => $locale,
            'data'   => $data,
        ]);
    }
}
