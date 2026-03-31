<?php

namespace App\Repositories;

use App\Contracts\ArticleRepositoryInterface;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Article::query()
            ->with(['author', 'categories'])
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function find(int $id): ?Article
    {
        return Article::query()->with(['author', 'categories'])->find($id);
    }

    public function create(array $data, array $categoryIds): Article
    {
        $article = Article::query()->create($data);
        $article->categories()->sync($categoryIds);
        $article->load(['author', 'categories']);

        return $article;
    }

    public function update(Article $article, array $data, ?array $categoryIds = null): Article
    {
        $article->update($data);
        if ($categoryIds !== null) {
            $article->categories()->sync($categoryIds);
        }
        $article->load(['author', 'categories']);

        return $article;
    }

    public function delete(Article $article): void
    {
        $article->delete();
    }

    public function slugExists(string $slug, ?int $exceptArticleId = null): bool
    {
        $q = Article::query()->where('slug', $slug);
        if ($exceptArticleId !== null) {
            $q->where('id', '!=', $exceptArticleId);
        }

        return $q->exists();
    }
}
