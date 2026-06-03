<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerAccount extends Model
{
    protected $fillable = ['narration', 'type'];

    public function transactions()
    {
        return $this->hasMany(LedgerTransaction::class);
    }
}
