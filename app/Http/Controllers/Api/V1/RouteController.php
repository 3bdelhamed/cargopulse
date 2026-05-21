<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Fleet\Actions\ArchiveRouteAction;
use App\Domains\Fleet\Actions\CancelRouteAction;
use App\Domains\Fleet\Actions\CompleteRouteAction;
use App\Domains\Fleet\Actions\CreateRouteManifestAction;
use App\Domains\Fleet\Actions\ReorderRouteStopsAction;
use App\Domains\Fleet\Actions\StartRouteAction;
use App\Domains\Fleet\Models\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderRouteStopsRequest;
use App\Http\Requests\Api\V1\StoreRouteRequest;
use App\Http\Resources\Api\V1\RouteResource;
use Illuminate\Http\JsonResponse;

class RouteController extends Controller
{
    public function store(StoreRouteRequest $request, CreateRouteManifestAction $action): JsonResponse
    {
        $route = $action->execute($request->toData());

        return response()->json([
            'message' => 'Route manifest created successfully',
            'data' => new RouteResource($route),
        ], 201);
    }

    public function start(Route $route, StartRouteAction $action): JsonResponse
    {
        $route = $action->execute($route);

        return response()->json([
            'message' => 'Route started successfully',
            'data' => new RouteResource($route->load('shipments')),
        ]);
    }

    public function complete(Route $route, CompleteRouteAction $action): JsonResponse
    {
        $route = $action->execute($route);

        return response()->json([
            'message' => 'Route completed successfully',
            'data' => new RouteResource($route),
        ]);
    }

    public function cancel(Route $route, CancelRouteAction $action): JsonResponse
    {
        $route = $action->execute($route);

        return response()->json([
            'message' => 'Route cancelled successfully',
            'data' => new RouteResource($route),
        ]);
    }

    public function archive(Route $route, ArchiveRouteAction $action): JsonResponse
    {
        $route = $action->execute($route);

        return response()->json([
            'message' => 'Route archived successfully',
            'data' => new RouteResource($route),
        ]);
    }

    public function reorder(Route $route, ReorderRouteStopsRequest $request, ReorderRouteStopsAction $action): JsonResponse
    {
        $route = $action->execute($route, $request->toData());

        return response()->json([
            'message' => 'Route stops reordered successfully',
            'data' => new RouteResource($route),
        ]);
    }
}
