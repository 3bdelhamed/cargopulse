<?php

namespace App\Domains\Analytics\Actions;

use App\Domains\Analytics\DTOs\PerformanceMetricsData;
use App\Domains\Shipments\Models\Shipment;
use App\Domains\Fleet\Models\Route;
use Illuminate\Support\Facades\DB;

class GetTenantDashboardMetricsAction
{
    /**
     * Computes real-time performance counters utilizing highly optimized database aggregates.
     * Guaranteed tenant-isolation due to global BelongsToTenant scope booting automatically.
     */
    public function execute(): PerformanceMetricsData
    {
        $totalShipments = Shipment::count();

        if ($totalShipments === 0) {
            return new PerformanceMetricsData(0.0, 0.0, 0.0, 0);
        }

        // Delivery Success Rate
        $deliveredCount = Shipment::where('state', 'delivered')->count();
        $successRate = ($deliveredCount / $totalShipments) * 100;

        // Average Delivery Duration (Hours) via database-level aggregation.
        $durationExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "(julianday(updated_at) - julianday(created_at)) * 86400"
            : "EXTRACT(EPOCH FROM (updated_at - created_at))";

        $avgDurationSeconds = Shipment::where('state', 'delivered')
            ->avg(DB::raw($durationExpression));
            
        $averageDurationHours = $avgDurationSeconds ? ($avgDurationSeconds / 3600) : 0.0;

        // Failed-to-Returned Ratio (Approximated as failure rate for this context)
        $failedCount = Shipment::where('state', 'failed')->count();
        $failedRatio = $failedCount > 0 ? ($failedCount / $totalShipments) * 100 : 0.0;

        // Active Drivers Count efficiently derived from active routes
        $activeDriversCount = Route::where('status', 'in_progress')
            ->distinct('driver_id')
            ->count('driver_id');

        return new PerformanceMetricsData(
            round($successRate, 2),
            round($averageDurationHours, 2),
            round($failedRatio, 2),
            $activeDriversCount
        );
    }
}
