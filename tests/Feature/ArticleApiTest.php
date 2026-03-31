<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_articles(): void
    {
        $this->getJson('/api/articles')->assertUnauthorized();
    }

    public function test_active_user_can_create_article_with_auto_slug(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/articles', [
            'title' => 'Mi primer artículo',
            'content' => 'Contenido',
            'status' => 'draft',
            'category_ids' => [$category->id],
        ]);

        $response->assertCreated();
        $this->assertSame('mi-primer-articulo', $response->json('data.slug'));
        $this->assertDatabaseHas('articles', [
            'title' => 'Mi primer artículo',
            'slug' => 'mi-primer-articulo',
            'user_id' => $user->id,
        ]);
    }

    public function test_inactive_user_cannot_create_article(): void
    {
        $user = User::factory()->inactive()->create();
        $category = Category::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/articles', [
            'title' => 'Título',
            'content' => 'Texto',
            'status' => 'draft',
            'category_ids' => [$category->id],
        ]);

        $response->assertForbidden();
    }

    public function test_slug_is_unique_when_title_collides(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Article::factory()->create([
            'title' => 'Mismo título',
            'slug' => 'mismo-titulo',
            'user_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/articles', [
            'title' => 'Mismo título',
            'content' => 'Otro',
            'status' => 'draft',
            'category_ids' => [$category->id],
        ]);

        $response->assertCreated();
        $this->assertSame('mismo-titulo-1', $response->json('data.slug'));
    }
}
