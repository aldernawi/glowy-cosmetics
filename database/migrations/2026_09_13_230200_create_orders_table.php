<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('number')->unique();
            $t->string('status');
            $t->string('payment_method')->default('cash_on_delivery');
            $t->string('payment_status')->default('unpaid');
            $t->string('customer_name');
            $t->string('phone');
            $t->decimal('delivery_latitude', 10, 7);
            $t->decimal('delivery_longitude', 10, 7);
            $t->decimal('delivery_distance_km', 12, 3);
            $t->decimal('delivery_fee', 12, 3);
            $t->decimal('subtotal', 12, 3);
            $t->decimal('discount', 12, 3)->default(0);
            $t->decimal('total', 12, 3);
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variant_id')->nullable()->nullOnDelete();
            $t->string('product_name');
            $t->string('variant_name')->nullable();
            $t->string('sku');
            $t->decimal('unit_price', 12, 3);
            $t->unsignedInteger('quantity');
            $t->decimal('subtotal', 12, 3);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
