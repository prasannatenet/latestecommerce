<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartDrawerCrossSellService
{
    /**
     * Return up to two unique cross-sell products for the products in the cart.
     *
     * @param Collection<int, array> $cartItems
     * @return Collection<int, array{id:int,name:string,slug:string,image:string,price:float}>
     */
    public function suggestions(Collection $cartItems): Collection
    {
        $cartProductIds = $cartItems->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($cartProductIds->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', $cartProductIds)
            ->with(['crossSellProducts.images', 'crossSellProducts.variations', 'crossSellProducts.reviews'])
            ->get();

        return $products
            ->flatMap(fn (Product $product) => $product->crossSellProducts)
            ->filter(fn (Product $product): bool => $product->is_active && ! $cartProductIds->contains((int) $product->id))
            ->unique('id')
            ->take(2)
            ->map(function (Product $product): array {
                $image = $product->primary_image
                    ?: $product->images->firstWhere('is_primary', true)?->path
                    ?: $product->images->first()?->path;

                return [
                    'id' => (int) $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $image
                        ? asset('storage/' . ltrim($image, '/'))
                        : asset('frontend/images/product-1.jpeg'),
                    'price' => $product->effectivePrice(),
                ];
            })
            ->values();
    }
}
