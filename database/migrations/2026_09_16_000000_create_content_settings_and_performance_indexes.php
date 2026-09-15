<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::table('orders', fn (Blueprint $table) => $table->index(['status', 'created_at']));
        Schema::table('products', fn (Blueprint $table) => $table->index(['is_active', 'is_featured', 'created_at']));
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropIndex(['is_active', 'is_featured', 'created_at']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropIndex(['status', 'created_at']));
        Schema::dropIfExists('settings');
    }
};
