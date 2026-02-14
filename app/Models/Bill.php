<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'amount',
        'category',
        'bill_account',
        'due_date',
        'is_paid',
        'is_cleared',
        'image',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'is_paid' => 'boolean',
        'is_cleared' => 'boolean',
    ];

    /**
     * Hide internal timestamps and soft delete column from JSON.
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function markAsPaid(): void
    {
        $this->update(['is_paid' => true]);
    }

    public function markAsCleared(): void
    {
        $this->update(['is_cleared' => true]);
    }
}
