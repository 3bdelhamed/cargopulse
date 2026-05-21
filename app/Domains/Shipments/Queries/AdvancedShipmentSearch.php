<?php

namespace App\Domains\Shipments\Queries;

use App\Domains\Shipments\Models\Shipment;
use App\Http\Requests\Api\V1\SearchShipmentsRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdvancedShipmentSearch
{
    /**
     * Multi-parameter advanced filtering pipeline.
     * Avoids deeply nested if-else blocks by utilizing native Eloquent 'when' scoping.
     */
    public function execute(array $filters): Builder
    {
        $query = Shipment::query();

        $query->when(isset($filters['state']), function (Builder $q) use ($filters) {
            $q->where('state', $filters['state']);
        })
        ->when(isset($filters['merchant_id']), function (Builder $q) use ($filters) {
            $q->where('merchant_id', $filters['merchant_id']);
        })
        ->when(isset($filters['tracking_number']), function (Builder $q) use ($filters) {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            $q->where('tracking_number', $operator, '%' . $filters['tracking_number'] . '%');
        })
        ->when(isset($filters['date_from']), function (Builder $q) use ($filters) {
            $q->whereDate('created_at', '>=', $filters['date_from']);
        })
        ->when(isset($filters['date_to']), function (Builder $q) use ($filters) {
            $q->whereDate('created_at', '<=', $filters['date_to']);
        });

        // Apply strict safe sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortColumns = SearchShipmentsRequest::sortColumns();
        
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }
}
