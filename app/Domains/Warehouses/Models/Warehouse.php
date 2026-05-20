<?php

namespace App\Domains\Warehouses\Models;

use App\Domains\Shipments\Models\Shipment;
use App\Domains\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
