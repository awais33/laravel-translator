<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Translations\ListTranslationsRequest;
use App\Http\Requests\Translations\StoreTranslationRequest;
use App\Http\Requests\Translations\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Http\Responses\ApiResponse;
use App\Http\Services\TranslationService;
use Illuminate\Http\JsonResponse;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationService $service
    ) {}

    public function index(ListTranslationsRequest $request): JsonResponse
    {
        $paginator = $this->service->list($request);

        return ApiResponse::paginated(
            TranslationResource::collection($paginator)->resolve(),
            $paginator
        );
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->service->create($request->validated());

        return ApiResponse::created(
            (new TranslationResource($translation))->resolve()
        );
    }

    public function show(int $id): JsonResponse
    {
        $translation = $this->service->find($id);

        return ApiResponse::success(
            (new TranslationResource($translation))->resolve()
        );
    }

    public function update(UpdateTranslationRequest $request, int $id): JsonResponse
    {
        $translation = $this->service->update($id, $request->validated());

        return ApiResponse::success(
            (new TranslationResource($translation))->resolve(),
            'Translation updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::deleted('Translation deleted successfully');
    }
}
