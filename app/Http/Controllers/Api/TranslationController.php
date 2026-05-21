<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['locale', 'tag', 'search', 'key', 'group']);
        $perPage = min((int) $request->query('per_page', 20), 100);

        $paginated = $this->service->list($filters, $perPage);

        return TranslationResource::collection($paginated);
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->service->create($request->validated());

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): TranslationResource
    {
        return new TranslationResource($this->service->find($id));
    }

    public function update(UpdateTranslationRequest $request, int $id): TranslationResource
    {
        $translation = $this->service->update($id, $request->validated());

        return new TranslationResource($translation);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return response()->json(['message' => 'Translation deleted.'], 200);
    }
}
