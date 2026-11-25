<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->string('barcode', 64)->nullable()->unique()->after('sku');
        });

        Schema::create('pos_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('pos_sales', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pos_device_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cashier_name');
            $table->string('receipt_number', 32)->index();
            $table->unsignedInteger('sequence');
            $table->string('status', 20);
            $table->string('payment_method', 20)->index();
            $table->string('payment_reference')->nullable();
            $table->decimal('total', 12, 3);
            $table->decimal('amount_received', 12, 3)->nullable();
            $table->decimal('change_due', 12, 3)->nullable();
            $table->dateTime('sold_at')->index();
            $table->dateTime('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'sold_at']);
        });

        Schema::create('pos_sale_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku');
            $table->decimal('unit_price', 12, 3);
            $table->decimal('catalog_price', 12, 3)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('stock_deducted')->default(0);
            $table->decimal('subtotal', 12, 3);
            $table->timestamps();
        });

        Schema::create('pos_stock_shortages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pos_sale_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('resolution', 20)->nullable();
            $table->dateTime('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->createPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_stock_shortages');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_devices');
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropUnique(['barcode']);
            $table->dropColumn('barcode');
        });
    }

    /** Created here as well as in the seeder so deploying only requires running the migrations. */
    private function createPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(['pos.use', 'pos.manage'])->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
        Role::findOrCreate('cashier', 'web')->givePermissionTo('pos.use');
        Role::query()->where('name', 'administrator')->where('guard_name', 'web')->first()?->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
