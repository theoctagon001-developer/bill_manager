<?php

namespace Tests\Feature;

use App\Models\Bill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test POST /api/bills - Create a new bill
     */
    public function test_can_create_bill(): void
    {
        $billData = [
            'amount' => 150.75,
            'category' => 'Utilities',
            'bill_account' => 'ACCOUNT-001',
            'due_date' => '2024-03-15',
            'is_paid' => false,
        ];

        $response = $this->postJson('/api/bills', $billData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Bill created successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'amount',
                    'category',
                    'bill_account',
                    'due_date',
                    'is_paid',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('bills', [
            'amount' => 150.75,
            'category' => 'Utilities',
            'bill_account' => 'ACCOUNT-001',
            'is_paid' => false,
        ]);
    }

    /**
     * Test POST /api/bills - Validation errors
     */
    public function test_create_bill_validation_errors(): void
    {
        $response = $this->postJson('/api/bills', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['amount', 'category', 'bill_account', 'due_date']);
    }

    /**
     * Test GET /api/bills - Retrieve bills with pagination
     */
    public function test_can_get_bills_with_pagination(): void
    {
        // Create some test bills
        Bill::factory()->count(20)->create();

        $response = $this->getJson('/api/bills?per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to',
                ],
            ]);

        $responseData = $response->json();
        $this->assertCount(10, $responseData['data']);
        $this->assertEquals(20, $responseData['pagination']['total']);
        $this->assertEquals(10, $responseData['pagination']['per_page']);
    }

    /**
     * Test GET /api/bills - Ordered by date desc
     */
    public function test_bills_ordered_by_date_desc(): void
    {
        // Create bills with different timestamps
        $oldBill = Bill::factory()->create(['created_at' => now()->subDays(2)]);
        $newBill = Bill::factory()->create(['created_at' => now()]);
        $middleBill = Bill::factory()->create(['created_at' => now()->subDay()]);

        $response = $this->getJson('/api/bills');

        $response->assertStatus(200);
        $bills = $response->json()['data'];

        // Check that bills are ordered by created_at desc (newest first)
        $this->assertEquals($newBill->id, $bills[0]['id']);
        $this->assertEquals($middleBill->id, $bills[1]['id']);
        $this->assertEquals($oldBill->id, $bills[2]['id']);
    }

    /**
     * Test POST /api/bills - Create bill with is_paid true
     */
    public function test_can_create_bill_with_is_paid_true(): void
    {
        $billData = [
            'amount' => 250.00,
            'category' => 'Internet',
            'bill_account' => 'ACCOUNT-002',
            'due_date' => '2024-03-20',
            'is_paid' => true,
        ];

        $response = $this->postJson('/api/bills', $billData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('bills', [
            'bill_account' => 'ACCOUNT-002',
            'is_paid' => true,
        ]);
    }
}
