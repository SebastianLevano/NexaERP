<?php

namespace Database\Factories;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'status' => SaleStatus::Draft,
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'issued_at' => now(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => SaleStatus::Confirmed]);
    }
}
