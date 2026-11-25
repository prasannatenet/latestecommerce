<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    protected $fillable = [
        'product_id', 'base_price', 'sale_price', 'change_type',
        'change_reason', 'changed_by', 'effective_at',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'effective_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
