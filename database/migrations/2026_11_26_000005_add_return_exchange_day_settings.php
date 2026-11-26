<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'default_return_days')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->unsignedSmallInteger('default_return_days')->nullable();
            });
        }

        if (! Schema::hasColumn('settings', 'default_exchange_days')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->unsignedSmallInteger('default_exchange_days')->nullable();
            });
        }

        if (! Schema::hasColumn('products', 'return_days')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->unsignedSmallInteger('return_days')->nullable();
            });
        }

        if (! Schema::hasColumn('products', 'exchange_days')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->unsignedSmallInteger('exchange_days')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ([['products', ['return_days', 'exchange_days']], ['settings', ['default_return_days', 'default_exchange_days']]] as [$tableName, $columns]) {
            $existing = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn($tableName, $column)));
            if ($existing !== []) {
                Schema::table($tableName, function (Blueprint $table) use ($existing): void {
                    $table->dropColumn($existing);
                });
            }
        }
    }
};
