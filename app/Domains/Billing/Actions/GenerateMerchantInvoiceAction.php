<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Jobs\CompileInvoicePdfJob;
use App\Domains\Billing\Models\Invoice;
use App\Domains\Billing\Models\LedgerTransaction;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Tenants\Models\Merchant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class GenerateMerchantInvoiceAction
{
    public function __construct(
        private readonly CalculateDeliveryFeeAction $calculateFeeAction
    ) {}

    /**
     * Compiles unbilled shipments, sums fees, creates an invoice, and dispatches a PDF job.
     */
    public function execute(Merchant $merchant, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        return DB::transaction(function () use ($merchant, $periodStart, $periodEnd) {
            // Lock unbilled shipments for this merchant in the given date range
            // Only bill shipments that have been successfully delivered
            $unbilledShipments = Shipment::where('merchant_id', $merchant->id)
                ->where('is_billed', false)
                ->where('state', 'delivered')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->lockForUpdate()
                ->get();

            if ($unbilledShipments->isEmpty()) {
                throw new Exception('No unbilled shipments found for this merchant in the given period.');
            }

            // Create Draft Invoice
            $invoice = Invoice::create([
                'merchant_id' => $merchant->id,
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'status' => 'generating', // Will be set to 'unpaid' when PDF compilation finishes
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_amount' => 0,
                'cod_collected' => 0,
            ]);

            $totalDeliveryFees = 0.00;
            $totalCodCollected = 0.00;

            foreach ($unbilledShipments as $shipment) {
                // Ensure fee is calculated if missing
                $fee = $shipment->delivery_fee ?? $this->calculateFeeAction->execute($shipment);
                $totalDeliveryFees += $fee;
                $totalCodCollected += (float) $shipment->cod_amount;

                // Reconcile Ledger: Delivery Fee Charge
                LedgerTransaction::create([
                    'merchant_id' => $merchant->id,
                    'invoice_id' => $invoice->id,
                    'shipment_id' => $shipment->id,
                    'type' => 'delivery_fee',
                    'amount' => -$fee, // Deduct fee from merchant balance
                    'description' => "Delivery fee for shipment {$shipment->tracking_number}",
                ]);

                // Reconcile Ledger: Cash on Delivery Collection
                if ($shipment->cod_amount > 0) {
                    LedgerTransaction::create([
                        'merchant_id' => $merchant->id,
                        'invoice_id' => $invoice->id,
                        'shipment_id' => $shipment->id,
                        'type' => 'cod_collection',
                        'amount' => $shipment->cod_amount, // Add collected COD to merchant balance
                        'description' => "COD collected for shipment {$shipment->tracking_number}",
                    ]);
                }

                // Mark shipment as billed to prevent double-counting
                $shipment->update([
                    'is_billed' => true,
                    'invoice_id' => $invoice->id,
                ]);
            }

            // Finalize Invoice totals before committing transaction
            $invoice->update([
                'total_amount' => $totalDeliveryFees,
                'cod_collected' => $totalCodCollected,
            ]);

            // Dispatch background queue job for deferred PDF compilation
            dispatch(new CompileInvoicePdfJob($invoice->id));

            return $invoice;
        });
    }
}
