<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_search_by_month_year(): void
    {
        Budget::create([
            'amount' => 5000,
            'month' => 4,
            'year' => 2026,
            'expense_assumed' => 2000,
            'saving_assumed' => 3000,
        ]);
        Expense::create([
            'amount' => 150,
            'category' => 'food',
            'spend_date' => '2026-04-05',
            'month' => 4,
            'year' => 2026,
        ]);
        $res = $this->getJson('/api/budgets/search?month=4&year=2026');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id','month','year','budget','expense_assumed','saving_assumed','summary','expenses'
                ],
            ]);
        $this->assertEquals('5000.00', $res->json()['data']['budget']);
    }

    public function test_expense_search_by_month_year(): void
    {
        Expense::create([
            'amount' => 100,
            'category' => 'food',
            'spend_date' => '2026-04-01',
            'month' => 4,
            'year' => 2026,
        ]);
        Expense::create([
            'amount' => 80,
            'category' => 'travel',
            'spend_date' => '2026-04-02',
            'month' => 4,
            'year' => 2026,
        ]);
        $res = $this->getJson('/api/expenses/search?month=4&year=2026&per_page=10');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success','data','grouped','pagination'
            ]);
        $grouped = collect($res->json()['grouped']);
        $this->assertEquals('100.00', $grouped->firstWhere('category', 'food')['total_expense']);
        $this->assertEquals('80.00', $grouped->firstWhere('category', 'travel')['total_expense']);
    }
}
