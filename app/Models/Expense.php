<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'supplier',
        'seller_id',
        'store_id',
        'expense_type_id',
        'amount',
        'payment_date',
        'closed_date',
        'status'
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }
}
