<?php

namespace App\Domains\Fleet\Actions;

use App\Domains\Fleet\Models\Route;
use App\Exceptions\DomainRuleException;
use Illuminate\Support\Facades\DB;

class ArchiveRouteAction
{
    public function execute(Route $route): Route
    {
        return DB::transaction(function () use ($route) {
            $route = Route::whereKey($route->id)->lockForUpdate()->firstOrFail();

            if (! in_array($route->status, [Route::STATUS_COMPLETED, Route::STATUS_CANCELLED], true)) {
                throw new DomainRuleException('Only completed or cancelled routes can be archived.');
            }

            $route->update(['status' => Route::STATUS_ARCHIVED]);

            return $route->load('shipments');
        });
    }
}
