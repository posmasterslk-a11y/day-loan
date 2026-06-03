<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'min_amount', 'max_amount', 'interest_rate', 'duration_days'];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
