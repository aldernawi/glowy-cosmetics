<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $t): void {
            $t->string('secondary_phone')->nullable();
            $t->string('delivery_address')->nullable();
            $t->string('delivery_place_id')->nullable();
            $t->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $t): void {
            $t->dropColumn(['secondary_phone', 'delivery_address', 'delivery_place_id', 'notes']);
        });
    }
};
