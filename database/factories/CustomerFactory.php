<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = fake()->randomElement([DocumentType::DNI, DocumentType::RUC]);

        return [
            'name' => $type === DocumentType::RUC
                ? fake()->company()
                : fake()->name(),
            'document_type' => $type,
            'document_number' => $type === DocumentType::RUC
                ? '20'.fake()->unique()->numerify('#########')
                : fake()->unique()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('9########'),
            'address' => fake()->optional()->streetAddress(),
            'notes' => null,
        ];
    }
}
