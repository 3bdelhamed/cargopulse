<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Analytics\Actions\GetTenantDashboardMetricsAction;
use App\Domains\Shipments\Queries\AdvancedShipmentSearch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchShipmentsRequest;
use App\Http\Resources\Api\V1\ShipmentResource;
use Illuminate\Http\JsonResponse;

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
    public function searchShipments(SearchShipmentsRequest $request, AdvancedShipmentSearch $searchPipeline): JsonResponse
    {
        $validatedFilters = $request->validated();

        $perPage = $validatedFilters['per_page'] ?? 25;

        $paginatedShipments = $searchPipeline->execute($validatedFilters)->paginate($perPage);

        return response()->json([
            'message' => 'Shipments retrieved successfully',
            'data' => ShipmentResource::collection($paginatedShipments)->response()->getData(true),
        ]);
    }
}
