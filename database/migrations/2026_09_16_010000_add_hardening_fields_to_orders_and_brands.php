<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_city_region')->nullable()->after('secondary_phone');
            $table->string('delivery_landmark')->nullable()->after('delivery_address');
            $table->unsignedInteger('delivery_duration_minutes')->nullable()->after('delivery_distance_km');
            $table->string('store_address_snapshot')->nullable()->after('store_longitude_snapshot');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->string('website')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn('website');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivery_city_region', 'delivery_landmark', 'delivery_duration_minutes', 'store_address_snapshot']);
        });
    }
};
