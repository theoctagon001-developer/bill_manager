<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_store_expense_and_link_to_budget(): void
    {
        $b = Budget::create([
            'amount' => 4000,
            'month' => 3,
            'year' => 2026,
            'expense_assumed' => 2000,
            'saving_assumed' => 2000,
        ]);
        $payload = [
            'amount' => 250.75,
            'category' => 'travel',
            'spend_date' => '2026-03-10',
        ];
        $res = $this->postJson('/api/expenses', $payload);
        $res->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Expense recorded successfully',
            ])
            ->assertJsonStructure([
                'data' => ['id','amount','category','spend_date','month','year','budget_id']
            ]);
        $data = $res->json()['data'];
        $this->assertEquals(3, $data['month']);
        $this->assertEquals(2026, $data['year']);
        $this->assertEquals($b->id, $data['budget_id']);
        $this->assertDatabaseHas('expenses', [
            'id' => $data['id'],
            'month' => 3,
            'year' => 2026,
            'budget_id' => $b->id,
        ]);
    }

    public function test_index_lists_and_groups_by_category(): void
    {
        Expense::create([
            'amount' => 100,
            'category' => 'food',
            'spend_date' => '2026-01-01',
            'month' => 1,
            'year' => 2026,
        ]);
        Expense::create([
            'amount' => 50,
            'category' => 'food',
            'spend_date' => '2026-01-02',
            'month' => 1,
            'year' => 2026,
        ]);
        Expense::create([
            'amount' => 80,
            'category' => 'travel',
            'spend_date' => '2026-01-03',
            'month' => 1,
            'year' => 2026,
        ]);
        $res = $this->getJson('/api/expenses?per_page=10&month=1&year=2026');
        $res->assertStatus(200)
            ->assertJsonStructure([
                'success','data','grouped','pagination'
            ]);
        $grouped = collect($res->json()['grouped']);
        $food = $grouped->firstWhere('category', 'food');
        $travel = $grouped->firstWhere('category', 'travel');
        $this->assertEquals('150.00', $food['total_expense']);
        $this->assertEquals('80.00', $travel['total_expense']);
    }

    public function test_update_and_delete_expense(): void
    {
        $e = Expense::create([
            'amount' => 10,
            'category' => 'misc',
            'spend_date' => '2026-02-01',
            'month' => 2,
            'year' => 2026,
        ]);
        $res = $this->putJson('/api/expenses/'.$e->id, ['amount' => 12.5]);
        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Expense updated successfully',
            ]);
        $this->assertDatabaseHas('expenses', [
            'id' => $e->id,
            'amount' => 12.5,
        ]);
        $res2 = $this->deleteJson('/api/expenses/'.$e->id);
        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Expense deleted successfully',
            ]);
        $this->assertDatabaseMissing('expenses', ['id' => $e->id]);
    }
}
