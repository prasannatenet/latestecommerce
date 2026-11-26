<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'gehna_coin_value')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->decimal('gehna_coin_value', 12, 4)->default(1.0000);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'gehna_coin_value')) {
            Schema::table('settings', function (Blueprint $table): void {
                $table->dropColumn('gehna_coin_value');
            });
        }
    }
};
