<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->string('change_type', 40);
            $table->string('change_reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_at')->index();
            $table->timestamps();
            $table->index(['product_id', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_histories');
    }
};
