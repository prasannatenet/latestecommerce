<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BraceletsProductsSeeder extends Seeder
{
    public function run(): void
    {
        // Parent category: Bracelets
        $bracelets = Category::firstOrCreate(
            ['slug' => 'bracelets'],
            [
                'name' => 'Bracelets',
                'parent_id' => null,
            ],
        );

        // Ensure Bracelets remains a parent category
        if ($bracelets->parent_id !== null) {
            $bracelets->update(['parent_id' => null]);
        }

        $products = [
            [
                'name' => 'Heart Charm Silver Bracelet',
                'sku' => 'G-BR-001',
                'base_price' => 1499,
                'sale_price' => 1199,
                'short_description' => 'Elegant silver bracelet featuring delicate heart charms for everyday wear and gifting.',
                'description' => <<<'DESCRIPTION'
Add a little charm to your everyday style with the Heart Charm Silver Bracelet.
This fictional jewellery design features a delicate silver chain with beautifully
shaped heart charms placed along its length.

Its simple and graceful design makes it suitable for casual outfits, special
occasions, and thoughtful gifting.

Product Features:
- Heart-shaped decorative charms
- Delicate chain bracelet design
- Minimal and elegant appearance
- Suitable for daily wear and gifting
- Lightweight-inspired design
DESCRIPTION,
            ],

            [
                'name' => 'Floral Dream Silver Bracelet',
                'sku' => 'G-BR-002',
                'base_price' => 1899,
                'sale_price' => 1549,
                'short_description' => 'Beautiful floral-inspired silver bracelet featuring delicate flower detailing and a feminine design.',
                'description' => <<<'DESCRIPTION'
Bring floral elegance to your jewellery collection with the Floral Dream Silver
Bracelet. This fictional design features small decorative flower motifs
connected by a delicate silver chain.

The floral details give it a soft and graceful appearance, making it a lovely
accessory for festive outfits, parties, or everyday styling.

Product Features:
- Flower-inspired decorative design
- Delicate silver chain
- Elegant and feminine appearance
- Suitable for festive and casual occasions
- Ideal for gifting
DESCRIPTION,
            ],

            [
                'name' => 'Infinity Grace Silver Bracelet',
                'sku' => 'G-BR-003',
                'base_price' => 1699,
                'sale_price' => 1349,
                'short_description' => 'Minimal silver bracelet with an infinity-inspired centerpiece symbolizing timeless elegance.',
                'description' => <<<'DESCRIPTION'
Keep your style simple and meaningful with the Infinity Grace Silver Bracelet.
This fictional bracelet features an infinity-shaped centerpiece attached to
a fine silver chain.

Its clean design makes it easy to pair with everyday outfits or layer with
other accessories. It also makes a thoughtful gift for someone special.

Product Features:
- Infinity-inspired centerpiece
- Fine silver chain design
- Minimal and modern style
- Comfortable everyday styling
- Suitable for gifting and special occasions
DESCRIPTION,
            ],

            [
                'name' => 'Leaf Elegance Silver Cuff Bracelet',
                'sku' => 'G-BR-004',
                'base_price' => 2299,
                'sale_price' => 1849,
                'short_description' => 'Stylish silver cuff bracelet with decorative leaf patterns and a nature-inspired design.',
                'description' => <<<'DESCRIPTION'
Inspired by the beauty of nature, the Leaf Elegance Silver Cuff Bracelet
features an artistic arrangement of decorative leaves along an elegant open-cuff
design.

The leaf pattern gives the bracelet a distinctive look while maintaining a
stylish balance between modern and natural design.

Product Features:
- Leaf-inspired engraved detailing
- Open cuff bracelet design
- Elegant decorative finish
- Contemporary nature-inspired styling
- Suitable for parties and special occasions
DESCRIPTION,
            ],

            [
                'name' => 'Crystal Shine Silver Bracelet',
                'sku' => 'G-BR-005',
                'base_price' => 2499,
                'sale_price' => 1999,
                'short_description' => 'Sparkling silver bracelet with a row of crystal-inspired decorative stones for an elegant look.',
                'description' => <<<'DESCRIPTION'
Make every occasion special with the Crystal Shine Silver Bracelet.
This fictional jewellery design features a sleek silver bracelet decorated
with a line of crystal-inspired stones that create a sparkling appearance.

Its elegant design works beautifully with party outfits, evening dresses,
and special occasion styling.

Product Features:
- Crystal-inspired decorative stones
- Elegant silver bracelet design
- Sparkling visual appearance
- Suitable for parties and evening occasions
- Beautiful gifting option
DESCRIPTION,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    ...$product,
                    'category_id' => $bracelets->id,
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