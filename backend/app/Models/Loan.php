<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_number', 'customer_id', 'amount', 'interest_rate', 'start_date',
        'term_days', 'total_payable', 'daily_installment', 'status',
        'loan_product_id', 'guarantor_id'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(LoanProduct::class, 'loan_product_id');
    }

    public function guarantor()
    {
        return $this->belongsTo(Guarantor::class);
    }

    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
