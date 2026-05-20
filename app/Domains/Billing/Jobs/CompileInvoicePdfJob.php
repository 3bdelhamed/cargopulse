<?php

namespace App\Domains\Billing\Jobs;

use App\Domains\Billing\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CompileInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $invoiceId
    ) {}

    public function handle(): void
    {
        $invoice = Invoice::with(['merchant', 'ledgerTransactions'])->find($this->invoiceId);

        if (!$invoice || $invoice->status !== 'generating') {
            return;
        }

        try {
            // Mock PDF generation process for the scope of this step.
            // Production systems would inject dompdf, snappy, or a headless browser renderer.
            Log::info("Compiling PDF for Invoice ID: {$invoice->id}");
            
            // Simulating compilation overhead ensuring HTTP layer remains unaffected
            sleep(1); 

            $pdfUrl = "https://cargopulse.s3.amazonaws.com/invoices/{$invoice->tenant_id}/{$invoice->invoice_number}.pdf";

            $invoice->update([
                'status' => 'unpaid',
                'pdf_url' => $pdfUrl,
            ]);

            Log::info("Successfully compiled PDF for Invoice ID: {$invoice->id}");

        } catch (\Throwable $th) {
            Log::error("Failed to compile PDF for Invoice ID: {$invoice->id}. Error: " . $th->getMessage());
            $invoice->update(['status' => 'failed']);
            throw $th;
        }
    }
}
