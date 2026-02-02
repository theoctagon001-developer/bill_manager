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

        $response->assertStatus(201)
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
                ],
            ]);
        $this->assertDatabaseHas('bills', [
            'bill_account' => 'ACCOUNT-002',
            'is_paid' => true,
        ]);
    }

    /**
     * Test GET /api/bills/{id} - Get a single bill
     */
    public function test_can_get_single_bill_by_id(): void
    {
        $bill = Bill::factory()->create([
            'amount' => 123.45,
            'category' => 'Internet',
            'bill_account' => 'ACCOUNT-9999',
        ]);

        $response = $this->getJson('/api/bills/'.$bill->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $bill->id,
                    'category' => 'Internet',
                    'bill_account' => 'ACCOUNT-9999',
                ],
            ]);
    }

    /**
     * Test PUT /api/bills/{id} - Update bill (partial update)
     */
    public function test_can_update_bill_partially(): void
    {
        $bill = Bill::factory()->create([
            'amount' => 100,
            'category' => 'Utilities',
        ]);

        $payload = [
            'amount' => 200.50,
        ];

        $response = $this->putJson('/api/bills/'.$bill->id, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bill updated successfully',
                'data' => [
                    'id' => $bill->id,
                    'amount' => '200.50',
                ],
            ]);

        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'amount' => 200.50,
        ]);
    }

    /**
     * Test DELETE /api/bills/{id} - Soft delete bill
     */
    public function test_can_soft_delete_bill(): void
    {
        $bill = Bill::factory()->create();

        $response = $this->deleteJson('/api/bills/'.$bill->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Bill deleted successfully',
            ]);

        // Ensure soft deleted (exists in table but with deleted_at set)
        $this->assertSoftDeleted('bills', [
            'id' => $bill->id,
        ]);
    }
}
