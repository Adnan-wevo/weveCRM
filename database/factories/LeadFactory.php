<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'source' => 'import',
            'status' => 'new',
            'contact_id' => Contact::factory(),
            'owner_id' => User::factory(),
            'score' => 0,
        ];
    }
}
