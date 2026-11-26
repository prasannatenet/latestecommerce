<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coupons', 'first_purchase_only')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->boolean('first_purchase_only')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('coupons', 'first_purchase_only')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->dropColumn('first_purchase_only');
            });
        }
    }
};
