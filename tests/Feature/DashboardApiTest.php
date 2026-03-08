<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_current_month_summary(): void
    {
        $now = now();
        Budget::create([
            'amount' => 3000,
            'month' => (int) $now->month,
            'year' => (int) $now->year,
            'expense_assumed' => 1000,
            'saving_assumed' => 2000,
        ]);
        Expense::create([
            'amount' => 600,
            'category' => 'food',
            'spend_date' => $now->toDateString(),
            'month' => (int) $now->month,
            'year' => (int) $now->year,
        ]);
        Expense::create([
            'amount' => 300,
            'category' => 'travel',
            'spend_date' => $now->toDateString(),
            'month' => (int) $now->month,
            'year' => (int) $now->year,
        ]);
        $res = $this->getJson('/api/dashboard');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'filters' => ['month','year'],
                'data' => [
                    'salary',
                    'expense' => ['assumption','real'],
                    'saving' => ['assumption','real'],
                    'limit_exceeded',
                    'expense_from_savings',
                    'remaining_balance',
                    'budget_usage',
                ],
            ]);
        $data = $res->json()['data'];
        $this->assertEquals('3000.00', $data['salary']);
        $this->assertEquals('2100.00', $data['remaining_balance']);
    }

    public function test_dashboard_specific_month_year(): void
    {
        Budget::create([
            'amount' => 5000,
            'month' => 4,
            'year' => 2026,
            'expense_assumed' => 1500,
            'saving_assumed' => 3500,
        ]);
        Expense::create([
            'amount' => 1200,
            'category' => 'food',
            'spend_date' => '2026-04-05',
            'month' => 4,
            'year' => 2026,
        ]);
        $res = $this->getJson('/api/dashboard?month=4&year=2026');
        $res->assertStatus(200);
        $data = $res->json()['data'];
        $this->assertEquals('5000.00', $data['salary']);
        $this->assertEquals('1500.00', $data['expense']['assumption']);
        $this->assertEquals('1200.00', $data['expense']['real']);
        $this->assertEquals('3500.00', $data['saving']['assumption']);
        $this->assertEquals('3800.00', $data['remaining_balance']);
    }
}
