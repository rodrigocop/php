<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryBusinessRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_delete_category_linked_to_article(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Article::factory()->create(['user_id' => $user->id])
            ->categories()
            ->attach($category);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/categories/'.$category->id);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'No se puede eliminar la categoría porque tiene artículos asociados.']);
    }
}
