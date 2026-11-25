<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\DB;

class ProductPriceHistoryService
{
    public function recordInitial(Product $product, ?int $changedBy = null, ?string $reason = null): ProductPriceHistory
    {
        return $product->priceHistories()->create([
            'base_price' => $product->base_price ?? 0,
            'sale_price' => $product->sale_price,
            'change_type' => 'tracking_start',
            'change_reason' => $reason,
            'changed_by' => $changedBy,
            'effective_at' => $product->created_at ?? now(),
        ]);
    }

    public function recordChange(Product $product, array $original, ?int $changedBy = null, ?string $reason = null): ?ProductPriceHistory
    {
        $baseChanged = $this->priceChanged($original['base_price'] ?? null, $product->base_price);
        $saleChanged = $this->priceChanged($original['sale_price'] ?? null, $product->sale_price);

        if (! $baseChanged && ! $saleChanged) {
            return null;
        }

        $types = [];
        if ($baseChanged) {
            $types[] = 'base_price';
        }
        if ($saleChanged) {
            $types[] = 'sale_price';
        }

        return $product->priceHistories()->create([
            'base_price' => $product->base_price ?? 0,
            'sale_price' => $product->sale_price,
            'change_type' => implode('_and_', $types) . '_changed',
            'change_reason' => $reason,
            'changed_by' => $changedBy,
            'effective_at' => now(),
        ]);
    }

    public function seedTrackingStarts(): int
    {
        return DB::transaction(function (): int {
            $created = 0;
            Product::query()->whereDoesntHave('priceHistories')->chunkById(200, function ($products) use (&$created): void {
                foreach ($products as $product) {
                    $this->recordInitial($product, null, 'Tracking started; earlier price history is unavailable.');
                    $created++;
                }
            });

            return $created;
        });
    }

    private function priceChanged(mixed $old, mixed $new): bool
    {
        if ($old === null || $new === null) {
            return $old !== $new;
        }

        return (int) round((float) $old * 100) !== (int) round((float) $new * 100);
    }
}
