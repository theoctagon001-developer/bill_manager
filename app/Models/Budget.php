<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'amount',
        'month',
        'year',
        'expense_assumed',
        'saving_assumed',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_assumed' => 'decimal:2',
        'saving_assumed' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
