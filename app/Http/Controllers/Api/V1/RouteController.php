<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Fleet\Actions\CreateRouteManifestAction;
use App\Domains\Fleet\Actions\ReorderRouteStopsAction;
use App\Domains\Fleet\Actions\StartRouteAction;
use App\Domains\Fleet\DTOs\ReorderRouteStopsData;
use App\Domains\Fleet\DTOs\RouteAssignmentData;
use App\Domains\Fleet\Models\Route;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class RouteController extends Controller
{
    public function store(RouteAssignmentData $data, CreateRouteManifestAction $action): JsonResponse
    {
        $route = $action->exe cute($data);

        return response()->json([
            'message' => 'Route manifest created successfully',
            'data' => $route,
        ], 201);
    }

    public function start(Route $route, StartRouteAction $action): JsonResponse
    {
        $route = $action->execute($route);

        return response()->json([
            'message' => 'Route started successfully',
            'data' => $route,
        ]);
    }

    public function reorder(Route $route, ReorderRouteStopsData $data, ReorderRouteStopsAction $action): JsonResponse
    {
        $route = $action->execute($route, $data);

        return response()->json([
            'message' => 'Route stops reordered successfully',
            'data' => $route,
        ]);
    }
}
