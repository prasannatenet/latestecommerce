<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RingsProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Parent category: Rings
        $rings = Category::firstOrCreate(
            ['slug' => 'rings'],
            [
                'name' => 'Rings',
                'parent_id' => null,
            ],
        );

        // Ensure Rings remains a parent category
        if ($rings->parent_id !== null) {
            $rings->update(['parent_id' => null]);
        }

        $products = [
            [
                'name' => 'Luna Floral Silver Ring',
                'sku' => 'G-R-001',
                'base_price' => 1499,
                'sale_price' => 1199,
                'short_description' => 'Elegant silver ring featuring a delicate floral centerpiece with a graceful polished finish.',
                'description' => <<<'DESCRIPTION'
Add a touch of floral elegance to your jewellery collection with the Luna Floral
Silver Ring. This fictional design features a beautifully crafted flower-inspired
centerpiece with delicate silver detailing.

Its graceful design makes it suitable for everyday wear, casual outings, and
special occasions.

Product Features:
- Floral-inspired centerpiece
- Elegant silver ring design
- Smooth polished finish
- Lightweight-inspired appearance
- Suitable for gifting and daily styling
DESCRIPTION,
            ],

            [
                'name' => 'Twilight Crystal Silver Ring',
                'sku' => 'G-R-002',
                'base_price' => 1899,
                'sale_price' => 1549,
                'short_description' => 'Stylish silver ring with a sparkling crystal-inspired centerpiece and modern detailing.',
                'description' => <<<'DESCRIPTION'
Discover the charm of the Twilight Crystal Silver Ring, a fictional jewellery
design created for a stylish and elegant look. It features a decorative
crystal-inspired centerpiece surrounded by subtle silver detailing.

The combination of sparkle and simplicity makes it a beautiful choice for
parties, celebrations, or thoughtful gifting.

Product Features:
- Crystal-inspired decorative centerpiece
- Elegant silver setting
- Modern and stylish appearance
- Suitable for special occasions
- Beautiful gifting option
DESCRIPTION,
            ],

            [
                'name' => 'Infinity Love Silver Ring',
                'sku' => 'G-R-003',
                'base_price' => 1699,
                'sale_price' => 1349,
                'short_description' => 'Minimal silver ring featuring an infinity-inspired design symbolizing a timeless connection.',
                'description' => <<<'DESCRIPTION'
Celebrate timeless connections with the Infinity Love Silver Ring. This
fictional design features an infinity-inspired pattern beautifully integrated
into a sleek silver band.

Its minimalist design gives it a meaningful and contemporary look, perfect
for everyday wear or as a gift for someone special.

Product Features:
- Infinity-inspired ring design
- Sleek silver band
- Minimal and contemporary styling
- Comfortable everyday appearance
- Suitable for gifting
DESCRIPTION,
            ],

            [
                'name' => 'Rose Petal Silver Ring',
                'sku' => 'G-R-004',
                'base_price' => 2299,
                'sale_price' => 1849,
                'short_description' => 'Beautiful silver ring featuring sculpted rose petal detailing and an artistic nature-inspired design.',
                'description' => <<<'DESCRIPTION'
Inspired by the beauty of blooming roses, the Rose Petal Silver Ring features
a fictional sculpted floral design with layered petal detailing.

The artistic centerpiece gives the ring a distinctive appearance while
maintaining a delicate and feminine style.

Product Features:
- Rose-inspired sculpted design
- Detailed silver petal centerpiece
- Artistic and elegant appearance
- Suitable for festive and party wear
- Unique jewellery gifting option
DESCRIPTION,
            ],

            [
                'name' => 'Celestial Star Silver Ring',
                'sku' => 'G-R-005',
                'base_price' => 2499,
                'sale_price' => 1999,
                'short_description' => 'Modern silver ring featuring a celestial star-inspired design with delicate decorative accents.',
                'description' => <<<'DESCRIPTION'
Bring a little celestial charm to your look with the Celestial Star Silver Ring.
This fictional jewellery design features a decorative star-shaped centerpiece
with delicate accents along a polished silver band.

Its dreamy and modern style makes it a lovely accessory for casual outfits,
evening occasions, and gifting.

Product Features:
- Star-inspired decorative centerpiece
- Polished silver band
- Celestial-inspired design
- Modern and stylish appearance
- Suitable for gifting and special occasions
DESCRIPTION,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    ...$product,
                    'category_id' => $rings->id,
                    'audience' => 'women',
                    'material_type' => 'silver',
                    'slug' => Str::slug($product['name']),
                    'is_active' => true,
                    'product_type' => 'simple',
                    'stock' => 0,
                    'manage_stock' => false,
                ],
            );
        }
    }
}
