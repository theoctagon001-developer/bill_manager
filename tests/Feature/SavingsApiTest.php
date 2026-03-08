<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_savings_keys(): void
    {
        Budget::create([
            'amount' => 3000,
            'month' => 1,
            'year' => 2026,
            'expense_assumed' => 1000,
            'saving_assumed' => 2000,
        ]);
        Budget::create([
            'amount' => 3500,
            'month' => 2,
            'year' => 2026,
            'expense_assumed' => 1200,
            'saving_assumed' => 2300,
        ]);
        Expense::create([
            'amount' => 800,
            'category' => 'food',
            'spend_date' => '2026-01-10',
            'month' => 1,
            'year' => 2026,
        ]);
        Expense::create([
            'amount' => 900,
            'category' => 'travel',
            'spend_date' => '2026-02-10',
            'month' => 2,
            'year' => 2026,
        ]);
        $res = $this->getJson('/api/savings');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'global' => [
                    'Total_Amount',
                    'Total_Expense_Assumed',
                    'Total_Saving_Assumed',
                    'Total_Actual_Saving',
                ],
            ]);
    }

    public function test_savings_with_date_range(): void
    {
        Budget::create([
            'amount' => 3000,
            'month' => 3,
            'year' => 2026,
            'expense_assumed' => 1000,
            'saving_assumed' => 2000,
        ]);
        Expense::create([
            'amount' => 600,
            'category' => 'food',
            'spend_date' => '2026-03-05',
            'month' => 3,
            'year' => 2026,
        ]);
        $res = $this->getJson('/api/savings?start_month=2026-03&end_month=2026-03');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'global' => [
                    'Total_Amount',
                    'Total_Expense_Assumed',
                    'Total_Saving_Assumed',
                    'Total_Actual_Saving',
                ],
                'range' => [
                    'start_month',
                    'end_month',
                ],
                'details',
            ]);
    }
}
