<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = ['room_number', 'status', 'total'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}