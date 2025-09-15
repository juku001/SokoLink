<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'supplier',
        'expense_type_id',
        'amount',
        'payment_date',
        'closed_date',
        'status'
    ];
}
