<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Shipments\Models\Shipment;

class CalculateDeliveryFeeAction
{
    /**
     * Compute shipping costs dynamically.
     * Isolated pricing matrix: Base rate + weight tier + zone (if applicable).
     */
    public function execute(Shipment $shipment): float
    {
        // For demonstration, a base flat rate configuration for the merchant or tenant
        $baseRate = 10.00;
        
        // Tiered weight pricing
        $weight = (float) $shipment->weight;
        $weightSurcharge = 0.00;

        if ($weight > 5.0 && $weight <= 10.0) {
            $weightSurcharge = 5.00;
        } elseif ($weight > 10.0) {
            // $2 per extra kg above 10
            $weightSurcharge = 5.00 + (($weight - 10.0) * 2.00);
        }

        // Potential Zone pricing logic could be implemented here using pickup/delivery coordinates
        $zoneSurcharge = 0.00; 

        $totalFee = $baseRate + $weightSurcharge + $zoneSurcharge;

        // Persist the fee back to the shipment immediately if not already set
        if (is_null($shipment->delivery_fee)) {
            $shipment->update(['delivery_fee' => $totalFee]);
        }

        return $totalFee;
    }
}
