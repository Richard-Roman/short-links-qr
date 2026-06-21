<?php

namespace RichardRoman\ShortLinks\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RichardRoman\ShortLinks\Laravel\Models\ShortLink;

/**
 * @extends Factory<ShortLink>
 */
class ShortLinkFactory extends Factory
{
    protected $model = ShortLink::class;

    public function definition(): array
    {
        return [
            'codigo' => strtolower($this->faker->regexify('[a-hjkmnp-z2-9]{8}')),
            'url_destino' => 'https://example.com/' . $this->faker->uuid(),
            'activo' => true,
            'total_clicks' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'activo' => false,
        ]);
    }
}
