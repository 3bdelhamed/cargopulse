<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Analytics\Actions\GetTenantDashboardMetricsAction;
use App\Domains\Shipments\Queries\AdvancedShipmentSearch;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Retrieve global KPI metrics for the tenant dashboard.
     * Endpoints remain fully strictly scoped to the active tenant.
     */
    public function metrics(GetTenantDashboardMetricsAction $action): JsonResponse
    {
        $metrics = $action->execute();

        return response()->json([
            'message' => 'Operational metrics retrieved successfully',
            'data' => $metrics,
        ]);
    }

    /**
     * Advanced filtering endpoint for searching shipments with strict database-level pagination.
     */
    public function searchShipments(Request $request, AdvancedShipmentSearch $searchPipeline): JsonResponse
    {
        // Minimal request validation specific to search filtering
        $validatedFilters = $request->validate([
            'state' => 'nullable|string',
            'merchant_id' => 'nullable|integer|exists:merchants,id',
            'tracking_number' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort_by' => 'nullable|string',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $validatedFilters['per_page'] ?? 25;
        
        // Execute dynamic query generation and perform native pagination
        $paginatedShipments = $searchPipeline->execute($validatedFilters)->paginate($perPage);

        return response()->json([
            'message' => 'Shipments retrieved successfully',
            'data' => $paginatedShipments,
        ]);
    }
}
