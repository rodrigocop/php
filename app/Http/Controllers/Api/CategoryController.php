<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CategoryInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $paginator = Category::query()
            ->orderBy('name')
            ->paginate($perPage);

        return CategoryResource::collection($paginator)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::query()->create($request->validated());

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Category $category): JsonResponse
    {
        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return (new CategoryResource($category->refresh()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->articles()->exists()) {
            throw new CategoryInUseException(
                'No se puede eliminar la categoría porque tiene artículos asociados.'
            );
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada correctamente.',
        ], Response::HTTP_OK);
    }
}
