<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartSession extends Model
{
    protected $fillable = ['user_id', 'status', 'subtotal', 'discount', 'total', 'session_id', 'started_at', 'last_activity_at', 'checkout_started_at', 'abandoned_at', 'converted_at', 'order_id'];

    protected $casts = ['started_at' => 'datetime', 'last_activity_at' => 'datetime', 'checkout_started_at' => 'datetime', 'abandoned_at' => 'datetime', 'converted_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(CartSessionItem::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function events() { return $this->hasMany(CustomerActivityEvent::class); }
}
