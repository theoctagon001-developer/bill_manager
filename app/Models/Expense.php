<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'budget_id',
        'amount',
        'category',
        'spend_date',
        'month',
        'year',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'spend_date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }
}
