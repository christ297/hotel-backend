<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = ['name', 'category', 'price', 'description', 'available', 'image'];

    protected $casts = [
        'available' => 'boolean',
        'price' => 'float'
    ];


     public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}