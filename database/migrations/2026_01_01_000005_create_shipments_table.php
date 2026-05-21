<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->string('tracking_number');
            $table->string('merchant_reference')->nullable();
            
            // Required for spatie/laravel-model-states integration
            $table->string('state'); 

            $table->string('priority')->default('normal');
            $table->text('pickup_address');
            $table->text('delivery_address');
            
            $table->decimal('pickup_lat', 10, 8)->nullable();
            $table->decimal('pickup_lng', 11, 8)->nullable();
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_lng', 11, 8)->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'tracking_number']);
            $table->unique(['tenant_id', 'merchant_id', 'merchant_reference']);
            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'tracking_number']);
            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'merchant_id', 'created_at']);
            $table->index(['tenant_id', 'state', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
