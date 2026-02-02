<?php

namespace Database\Seeders;

use App\Models\Bill;
use Illuminate\Database\Seeder;

class BillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert specific sample bills
        Bill::create([
            'amount' => 3500,
            'category' => 'WiFi',
            'bill_account' => 'Apa Seema',
            'due_date' => '2026-02-15',
            'is_paid' => false,
        ]);

        Bill::create([
            'amount' => 8000,
            'category' => 'Electricity',
            'bill_account' => 'Baji Sana',
            'due_date' => '2026-02-20',
            'is_paid' => true,
        ]);

        Bill::create([
            'amount' => 2500,
            'category' => 'Gas',
            'bill_account' => 'Our House',
            'due_date' => '2026-02-25',
            'is_paid' => false,
        ]);

        Bill::create([
            'amount' => 1800,
            'category' => 'Utilities',
            'bill_account' => 'Amir House',
            'due_date' => '2026-02-10',
            'is_paid' => true,
        ]);
    }
}
