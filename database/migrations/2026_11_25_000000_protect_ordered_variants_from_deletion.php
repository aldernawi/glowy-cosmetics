<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Variants could previously be deleted while order items still pointed at them.
        DB::table('order_items')
            ->whereNotNull('product_variant_id')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('product_variants')->whereColumn('product_variants.id', 'order_items.product_variant_id'))
            ->update(['product_variant_id' => null]);

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropForeign(['product_variant_id']);
        });
    }
};
