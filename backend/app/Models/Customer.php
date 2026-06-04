<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'full_name',
        'nic',
        'email',
        'dob',
        'gender',
        'phone',
        'whatsapp',
        'address',
        'business_type',
        'monthly_income',
        'photo_path',
        'nic_front_path',
        'nic_back_path',
        'status',
        'officer_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_id)) {
                $lastCustomer = static::orderBy('id', 'desc')->first();
                $lastId = $lastCustomer ? intval(substr($lastCustomer->customer_id, 4)) : 0;
                $customer->customer_id = 'CUS-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
