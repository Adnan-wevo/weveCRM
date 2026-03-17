<?php

namespace Modules\CRM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CRM\Models\Deal;

class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'stage' => $this->faker->randomElement(['new', 'qualified', 'proposal', 'negotiation', 'won', 'lost']),
            'status' => $this->faker->randomElement(['open', 'won', 'lost']),
            'value' => $this->faker->randomFloat(2, 1000, 50000),
            'currency' => 'MYR',
        ];
    }
}
