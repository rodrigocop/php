<?php

namespace App\Strategies;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\SlugGenerationStrategyInterface;
use Illuminate\Support\Str;

class UniqueSlugFromTitleStrategy implements SlugGenerationStrategyInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articles,
    ) {}

    public function generate(string $title, ?int $existingArticleId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'articulo';
        }

        $slug = $base;
        $i = 1;
        while ($this->articles->slugExists($slug, $existingArticleId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
