<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartSessionItem extends Model
{
    protected $fillable = ['cart_session_id', 'product_id', 'product_variation_id', 'combo_id', 'product_name', 'sku', 'quantity', 'unit_price', 'line_total', 'added_at', 'removed_at'];

    protected $casts = ['added_at' => 'datetime', 'removed_at' => 'datetime', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function cartSession() { return $this->belongsTo(CartSession::class); }
}
