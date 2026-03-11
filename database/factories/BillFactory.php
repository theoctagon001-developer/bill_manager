<?php

namespace Database\Factories;

use App\Models\Bill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bill>
 */
class BillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Utilities', 'Internet', 'Phone', 'Rent', 'Insurance', 'Credit Card', 'Loan'];
        
        return [
            'amount' => fake()->randomFloat(2, 10, 1000),
            'category' => fake()->randomElement($categories),
            'bill_account' => 'ACCOUNT-' . fake()->numerify('####'),
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'is_paid' => fake()->boolean(30), 
        ];
    }
}
