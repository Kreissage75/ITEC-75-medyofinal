<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'company', 'status', 'last_message',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'customer_name', 'name');
    }
}
