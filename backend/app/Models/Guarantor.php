<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'nic',
        'phone',
        'address',
        'relationship',
        'photo_path',
    ];

    public function customers()
    {
        return $this->belongsToMany(Customer::class);
    }
}
