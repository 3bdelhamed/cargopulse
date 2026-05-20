<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('invoice_number');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('cod_collected', 12, 2)->default(0);
            $table->string('status')->default('draft'); // draft, generating, unpaid, paid, failed
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('pdf_url')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_number']);
            $table->index(['tenant_id', 'merchant_id']);
        });

        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            
            $table->string('type'); // delivery_fee, cod_collection, payout, adjustment
            $table->decimal('amount', 12, 2); 
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'merchant_id']);
            $table->index(['invoice_id']);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->decimal('cod_amount', 10, 2)->default(0);
            $table->boolean('is_billed')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['weight', 'delivery_fee', 'cod_amount', 'is_billed', 'invoice_id']);
        });
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('invoices');
    }
};
