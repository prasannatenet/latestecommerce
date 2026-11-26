<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coupons', 'birthday_discount_type')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->string('birthday_discount_type', 16)->default('percent');
            });
        }

        if (Schema::hasTable('coupon_uses')) {
            if (! Schema::hasColumn('coupon_uses', 'redemption_year')) {
            Schema::table('coupon_uses', function (Blueprint $table): void {
                $table->unsignedSmallInteger('redemption_year')->nullable();
            });
            }

            DB::table('coupon_uses')->whereNull('redemption_year')->update(['redemption_year' => 0]);

            $indexNames = collect(Schema::getIndexes('coupon_uses'))->pluck('name')->all();
            if (in_array('coupon_uses_coupon_id_user_id_unique', $indexNames, true)) {
                Schema::table('coupon_uses', function (Blueprint $table): void {
                    $table->index('coupon_id', 'coupon_uses_coupon_id_support_index');
                    $table->dropUnique('coupon_uses_coupon_id_user_id_unique');
                });
                $indexNames = collect(Schema::getIndexes('coupon_uses'))->pluck('name')->all();
            }

            if (! in_array('coupon_uses_coupon_user_year_unique', $indexNames, true)) {
                Schema::table('coupon_uses', function (Blueprint $table): void {
                    $table->unique(['coupon_id', 'user_id', 'redemption_year'], 'coupon_uses_coupon_user_year_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('coupon_uses') && Schema::hasColumn('coupon_uses', 'redemption_year')) {
            Schema::table('coupon_uses', function (Blueprint $table): void {
                $table->dropUnique('coupon_uses_coupon_user_year_unique');
                $table->unique(['coupon_id', 'user_id'], 'coupon_uses_coupon_id_user_id_unique');
                $table->dropIndex('coupon_uses_coupon_id_support_index');
                $table->dropColumn('redemption_year');
            });
        }

        if (Schema::hasColumn('coupons', 'birthday_discount_type')) {
            Schema::table('coupons', function (Blueprint $table): void {
                $table->dropColumn('birthday_discount_type');
            });
        }
    }
};
