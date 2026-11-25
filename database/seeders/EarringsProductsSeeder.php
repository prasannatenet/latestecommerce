<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EarringsProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Parent category: Earrings
        $earrings = Category::firstOrCreate(
            ['slug' => 'earrings'],
            [
                'name' => 'Earrings',
                'parent_id' => null,
            ],
        );

        // Ensure Earrings remains a parent category
        if ($earrings->parent_id !== null) {
            $earrings->update(['parent_id' => null]);
        }

        $products = [
            [
                'name' => 'Moonlight Pearl Drop Earrings',
                'sku' => 'G-ER-001',
                'base_price' => 1499,
                'sale_price' => 1199,
                'short_description' => 'Elegant silver drop earrings featuring pearl-inspired accents and a delicate hanging design.',
                'description' => <<<'DESCRIPTION'
Discover timeless elegance with the Moonlight Pearl Drop Earrings. This
fictional jewellery design features a graceful pearl-inspired centerpiece
with a delicate silver drop pattern.

The elegant silhouette makes it perfect for festive outfits, evening wear,
special occasions, and gifting.

Product Features:
- Pearl-inspired decorative centerpiece
- Elegant silver drop design
- Delicate and graceful appearance
- Suitable for festive and party wear
- Beautiful gifting option
DESCRIPTION,
            ],

            [
                'name' => 'Floral Bloom Silver Stud Earrings',
                'sku' => 'G-ER-002',
                'base_price' => 1899,
                'sale_price' => 1549,
                'short_description' => 'Beautiful floral silver stud earrings featuring detailed petals and a polished decorative centerpiece.',
                'description' => <<<'DESCRIPTION'
Add floral charm to your jewellery collection with Floral Bloom Silver Stud
Earrings. This fictional design features a flower-shaped centerpiece with
delicately sculpted petals and a smooth silver finish.

Their compact and elegant design makes them suitable for daily styling,
festive occasions, and thoughtful gifting.

Product Features:
- Flower-inspired design
- Detailed petal pattern
- Polished silver finish
- Elegant stud-style fitting
- Suitable for everyday and festive wear
DESCRIPTION,
            ],

            [
                'name' => 'Infinity Spark Silver Earrings',
                'sku' => 'G-ER-003',
                'base_price' => 1699,
                'sale_price' => 1349,
                'short_description' => 'Modern silver earrings featuring an infinity-inspired design with delicate sparkling accents.',
                'description' => <<<'DESCRIPTION'
Express timeless beauty with Infinity Spark Silver Earrings. This fictional
jewellery design features an infinity-inspired pattern decorated with subtle
sparkling accents.

The modern design gives these earrings a versatile look that pairs beautifully
with casual outfits, dresses, and special occasion wear.

Product Features:
- Infinity-inspired design
- Delicate sparkling decorative accents
- Silver finish
- Modern and elegant styling
- Suitable for gifting
DESCRIPTION,
            ],

            [
                'name' => 'Rose Petal Silver Jhumki Earrings',
                'sku' => 'G-ER-004',
                'base_price' => 2299,
                'sale_price' => 1849,
                'short_description' => 'Traditional-inspired silver jhumki earrings featuring rose petal detailing and a decorative bell-shaped design.',
                'description' => <<<'DESCRIPTION'
Celebrate traditional elegance with Rose Petal Silver Jhumki Earrings.
This fictional design combines rose-inspired decorative detailing with
a classic bell-shaped jhumki silhouette.

The intricate floral appearance makes these earrings perfect for ethnic
wear, weddings, festivals, and special occasions.

Product Features:
- Rose-inspired decorative pattern
- Bell-shaped jhumki design
- Traditional silver appearance
- Detailed ornamental finish
- Ideal for festive and wedding wear
DESCRIPTION,
            ],

            [
                'name' => 'Celestial Star Silver Hoop Earrings',
                'sku' => 'G-ER-005',
                'base_price' => 2499,
                'sale_price' => 1999,
                'short_description' => 'Stylish silver hoop earrings featuring tiny star-inspired charms and a celestial decorative design.',
                'description' => <<<'DESCRIPTION'
Add a dreamy touch to your look with Celestial Star Silver Hoop Earrings.
This fictional jewellery design features elegant silver hoops decorated
with tiny star-inspired charms.

The combination of hoop styling and celestial details creates a playful
yet elegant look suitable for everyday wear and special occasions.

Product Features:
- Hoop-style earrings
- Star-inspired decorative charms
- Polished silver finish
- Modern celestial design
- Suitable for casual and party wear
DESCRIPTION,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    ...$product,
                    'category_id' => $earrings->id,
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
