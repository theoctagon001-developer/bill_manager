<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_budget_and_compute_saving(): void
    {
        $payload = [
            'amount' => 5000,
            'month' => 3,
            'year' => 2026,
            'expense' => 1500,
        ];
        $res = $this->postJson('/api/budgets', $payload);
        $res->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Budget created successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id','month','year','budget','expense_assumed','saving_assumed','summary','expenses'
                ]
            ]);
        $this->assertDatabaseHas('budgets', [
            'month' => 3,
            'year' => 2026,
            'amount' => 5000,
            'expense_assumed' => 1500,
            'saving_assumed' => 3500,
        ]);
    }

    public function test_index_returns_summary_and_pagination(): void
    {
        $b = Budget::create([
            'amount' => 2000,
            'month' => 3,
            'year' => 2026,
            'expense_assumed' => 1000,
            'saving_assumed' => 1000,
        ]);
        Expense::create([
            'budget_id' => $b->id,
            'amount' => 120,
            'category' => 'food',
            'spend_date' => '2026-03-05',
            'month' => 3,
            'year' => 2026,
        ]);
        $res = $this->getJson('/api/budgets?per_page=10');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success','data','pagination' => ['current_page','per_page','total','last_page','from','to']
            ]);
        $data = $res->json()['data'][0];
        $this->assertEquals('2000.00', $data['budget']);
        $this->assertEquals('1000.00', $data['expense_assumed']);
        $this->assertEquals('1000.00', $data['saving_assumed']);
        $this->assertEquals('120.00', $data['summary']['actual_expense']);
    }

    public function test_can_get_budget_by_id(): void
    {
        $b = Budget::create([
            'amount' => 3000,
            'month' => 4,
            'year' => 2026,
            'expense_assumed' => 1500,
            'saving_assumed' => 1500,
        ]);
        $res = $this->getJson('/api/budgets/'.$b->id);
        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $b->id,
                    'month' => 4,
                    'year' => 2026,
                ],
            ]);
    }

    public function test_can_update_and_delete_budget(): void
    {
        $b = Budget::create([
            'amount' => 3000,
            'month' => 5,
            'year' => 2026,
            'expense_assumed' => 1000,
            'saving_assumed' => 2000,
        ]);
        $res = $this->putJson('/api/budgets/'.$b->id, ['expense' => 1200]);
        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Budget updated successfully',
            ]);
        $this->assertDatabaseHas('budgets', [
            'id' => $b->id,
            'expense_assumed' => 1200,
            'saving_assumed' => 1800,
        ]);
        $res2 = $this->deleteJson('/api/budgets/'.$b->id);
        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Budget deleted successfully',
            ]);
        $this->assertDatabaseMissing('budgets', ['id' => $b->id]);
    }
}
