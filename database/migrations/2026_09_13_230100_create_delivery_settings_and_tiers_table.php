<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_settings', function (Blueprint $table): void {
            $table->id();
            $table->decimal('store_latitude', 10, 7)->nullable();
            $table->decimal('store_longitude', 10, 7)->nullable();
            $table->string('store_formatted_address')->nullable();
            $table->string('store_place_id')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_tiers', function (Blueprint $table): void {
            $table->id();
            $table->decimal('min_distance_km', 12, 3);
            $table->decimal('max_distance_km', 12, 3);
            $table->decimal('fee', 12, 3);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['is_active', 'min_distance_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_tiers');
        Schema::dropIfExists('delivery_settings');
    }
};
