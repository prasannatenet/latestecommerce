<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerActivityEvent extends Model
{
    protected $fillable = ['user_id', 'event_type', 'product_id', 'cart_session_id', 'order_id', 'quantity', 'price', 'total', 'properties', 'session_id'];

    protected $casts = ['properties' => 'array', 'price' => 'decimal:2', 'total' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function cartSession() { return $this->belongsTo(CartSession::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
