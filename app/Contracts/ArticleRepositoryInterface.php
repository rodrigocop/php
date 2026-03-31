<?php

namespace App\Contracts;

use App\Models\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ArticleRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Article;

    public function create(array $data, array $categoryIds): Article;

    public function update(Article $article, array $data, ?array $categoryIds = null): Article;

    public function delete(Article $article): void;

    public function slugExists(string $slug, ?int $exceptArticleId = null): bool;
}
