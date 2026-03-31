<?php

namespace App\Providers;

use App\Contracts\ArticleRepositoryInterface;
use App\Contracts\SlugGenerationStrategyInterface;
use App\Repositories\ArticleRepository;
use App\Strategies\UniqueSlugFromTitleStrategy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ArticleRepositoryInterface::class, ArticleRepository::class);
        $this->app->bind(SlugGenerationStrategyInterface::class, UniqueSlugFromTitleStrategy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
