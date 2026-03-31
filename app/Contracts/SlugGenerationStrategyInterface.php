<?php

namespace App\Contracts;

interface SlugGenerationStrategyInterface
{
    public function generate(string $title, ?int $existingArticleId = null): string;
}
