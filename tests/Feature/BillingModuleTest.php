<?php

namespace Tests\Feature;

use App\Domains\Billing\Actions\CalculateDeliveryFeeAction;
use App\Domains\Billing\Actions\GenerateMerchantInvoiceAction;
use App\Domains\Billing\Jobs\CompileInvoicePdfJob;
use App\Domains\Shipments\States\DeliveredState;
use App\Domains\Shipments\States\FailedState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\Support\BuildsLogisticsData;
use Tests\TestCase;

class BillingModuleTest extends TestCase
{
    use BuildsLogisticsData;
    use RefreshDatabase;

    public function test_delivery_fee_uses_weight_tiers_and_persists_missing_fee(): void
    {
        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $shipment = $this->shipmentForTenant($tenant, $merchant, [
            'weight' => 12.5,
            'delivery_fee' => null,
        ]);

        $fee = app(CalculateDeliveryFeeAction::class)->execute($shipment);

        $this->assertSame(20.0, $fee);
        $this->assertSame(20.0, (float) $shipment->refresh()->delivery_fee);
    }

    public function test_invoice_generation_reconciles_only_delivered_unbilled_shipments(): void
    {
        Queue::fake();

        $tenant = $this->tenant();
        $user = $this->userForTenant($tenant);
        $merchant = $this->merchantForTenant($tenant);

        $this->actingAs($user);

        $billable = $this->shipmentForTenant($tenant, $merchant, [
            'state' => DeliveredState::$name,
            'delivery_fee' => 15,
            'cod_amount' => 90,
            'created_at' => Carbon::parse('2026-05-10'),
        ]);

        $this->shipmentForTenant($tenant, $merchant, [
            'state' => FailedState::$name,
            'delivery_fee' => 15,
            'cod_amount' => 90,
            'created_at' => Carbon::parse('2026-05-10'),
        ]);

        $invoice = app(GenerateMerchantInvoiceAction::class)->execute(
            $merchant,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31')
        );

        $this->assertSame($tenant->id, $invoice->tenant_id);
        $this->assertSame(15.0, (float) $invoice->refresh()->total_amount);
        $this->assertSame(90.0, (float) $invoice->cod_collected);
        $this->assertTrue((bool) $billable->refresh()->is_billed);
        $this->assertDatabaseHas('ledger_transactions', [
            'invoice_id' => $invoice->id,
            'shipment_id' => $billable->id,
            'type' => 'delivery_fee',
            'amount' => -15,
        ]);
        $this->assertDatabaseHas('ledger_transactions', [
            'invoice_id' => $invoice->id,
            'shipment_id' => $billable->id,
            'type' => 'cod_collection',
            'amount' => 90,
        ]);
        Queue::assertPushed(CompileInvoicePdfJob::class);
    }
}
