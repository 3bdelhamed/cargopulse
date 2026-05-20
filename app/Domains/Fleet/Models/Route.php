<?php

namespace App\Domains\Fleet\Models;

use App\Domains\Shipments\Models\Shipment;
use App\Domains\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class)->orderBy('route_sequence', 'asc');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
