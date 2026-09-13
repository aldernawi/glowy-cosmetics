<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t): void {
            $t->decimal('store_latitude_snapshot', 10, 7);
            $t->decimal('store_longitude_snapshot', 10, 7);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t): void {
            $t->dropColumn(['store_latitude_snapshot', 'store_longitude_snapshot']);
        });
    }
};
