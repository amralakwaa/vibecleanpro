<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_category_id' => null,
            'featured_media_id' => null,
            'title' => fake()->sentence(4),
            'excerpt' => fake()->sentence(),
        ];
    }
}
