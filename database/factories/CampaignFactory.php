<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\OrganizerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $title = fake()->sentence(5, false);

        return [
            'organizer_profile_id' => OrganizerProfile::factory()->approved(),
            'category_id' => CampaignCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'short_description' => fake()->paragraph(2),
            'story' => fake()->paragraphs(4, true),
            'goal_amount' => fake()->randomFloat(2, 500, 100000),
            'raised_amount' => 0.00,
            'currency' => 'USD',
            'cover_image_path' => null,
            'status' => 'draft',
            'is_verified' => false,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(fake()->numberBetween(30, 180))->toDateString(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_verified' => true,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_verified' => false,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'is_verified' => true,
            'completed_at' => now(),
        ]);
    }
}
