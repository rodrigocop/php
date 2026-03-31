<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\SlugGenerationStrategyInterface;
use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
        private readonly SlugGenerationStrategyInterface $slugStrategy,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);
        $paginator = $this->articles->paginate($perPage);

        return ArticleResource::collection($paginator)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = $this->toArticleStatus($validated['status']);

        $publishedAt = $this->resolvePublishedAtForCreate($status, $validated['published_at'] ?? null);

        $slug = $this->slugStrategy->generate($validated['title']);

        $article = $this->articles->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'slug' => $slug,
            'status' => $status,
            'published_at' => $publishedAt,
            'user_id' => $request->user()->id,
        ], $validated['category_ids']);

        return (new ArticleResource($article))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Article $article): JsonResponse
    {
        $article->load(['author', 'categories']);

        return (new ArticleResource($article))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        $validated = $request->validated();
        $title = $validated['title'] ?? $article->title;
        $content = $validated['content'] ?? $article->content;

        $status = isset($validated['status'])
            ? $this->toArticleStatus($validated['status'])
            : $article->status;

        $publishedAtProvided = array_key_exists('published_at', $validated);
        $publishedAtInput = $publishedAtProvided ? $validated['published_at'] : null;
        $publishedAt = $this->resolvePublishedAtForUpdate(
            $status,
            $article,
            $publishedAtProvided,
            $publishedAtInput,
        );

        $slug = $article->slug;
        if (isset($validated['title']) && $validated['title'] !== $article->title) {
            $slug = $this->slugStrategy->generate($title, $article->id);
        }

        $data = [
            'title' => $title,
            'content' => $content,
            'slug' => $slug,
            'status' => $status,
            'published_at' => $publishedAt,
        ];

        $categoryIds = $validated['category_ids'] ?? null;

        $this->articles->update($article, $data, $categoryIds);
        $article->refresh()->load(['author', 'categories']);

        return (new ArticleResource($article))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function destroy(Article $article): JsonResponse
    {
        $this->articles->delete($article);

        return response()->json([
            'message' => 'Artículo eliminado correctamente.',
        ], Response::HTTP_OK);
    }

    private function toArticleStatus(mixed $value): ArticleStatus
    {
        if ($value instanceof ArticleStatus) {
            return $value;
        }

        return ArticleStatus::from((string) $value);
    }

    private function resolvePublishedAtForCreate(ArticleStatus $status, ?string $publishedAtInput): ?Carbon
    {
        if ($status === ArticleStatus::Draft) {
            return null;
        }

        return $publishedAtInput !== null
            ? Carbon::parse($publishedAtInput)
            : now();
    }

    private function resolvePublishedAtForUpdate(
        ArticleStatus $status,
        Article $article,
        bool $publishedAtProvided,
        ?string $publishedAtInput,
    ): ?Carbon {
        if ($status === ArticleStatus::Draft) {
            return null;
        }

        if ($publishedAtProvided && $publishedAtInput !== null) {
            return Carbon::parse($publishedAtInput);
        }

        if ($publishedAtProvided && $publishedAtInput === null) {
            return now();
        }

        return $article->published_at ?? now();
    }
}
