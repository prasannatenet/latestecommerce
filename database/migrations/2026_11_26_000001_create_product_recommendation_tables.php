<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_upsells')) {
            Schema::create('product_upsells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('upsell_product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['product_id', 'upsell_product_id']);
            });
        }

        if (!Schema::hasTable('product_cross_sells')) {
            Schema::create('product_cross_sells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cross_sell_product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['product_id', 'cross_sell_product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_cross_sells');
        Schema::dropIfExists('product_upsells');
    }
};
