<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cart_sessions')) {
            Schema::create('cart_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('status', 20)->default('active')->index();
                $table->decimal('subtotal', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('total', 10, 2)->default(0);
                $table->string('session_id')->nullable()->index();
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('last_activity_at')->useCurrent()->index();
                $table->timestamp('checkout_started_at')->nullable();
                $table->timestamp('abandoned_at')->nullable();
                $table->timestamp('converted_at')->nullable();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cart_session_items')) {
            Schema::create('cart_session_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('cart_session_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedBigInteger('product_variation_id')->nullable();
                $table->unsignedBigInteger('combo_id')->nullable();
                $table->string('product_name');
                $table->string('sku')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('line_total', 10, 2);
                $table->timestamp('added_at');
                $table->timestamp('removed_at')->nullable()->index();
                $table->timestamps();
                $table->index(['cart_session_id', 'product_id', 'product_variation_id', 'combo_id'], 'cart_session_items_lookup');
            });
        }

        if (! Schema::hasTable('customer_activity_events')) {
            Schema::create('customer_activity_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('event_type', 60)->index();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('cart_session_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedInteger('quantity')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->decimal('total', 10, 2)->nullable();
                $table->json('properties')->nullable();
                $table->string('session_id')->nullable()->index();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        // Keep activity records intact during rollback; this migration repairs
        // installations whose original migration was recorded without its tables.
    }
};
