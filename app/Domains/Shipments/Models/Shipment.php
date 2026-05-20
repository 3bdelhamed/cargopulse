<?php

namespace App\Domains\Shipments\Models;

use App\Domains\Shipments\States\ShipmentState;
use App\Domains\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

class Shipment extends Model
{
    use HasFactory;
    use HasStates;
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'state' => ShipmentState::class,
            'pickup_lat' => 'decimal:8',
            'pickup_lng' => 'decimal:8',
            'delivery_lat' => 'decimal:8',
            'delivery_lng' => 'decimal:8',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Tenants\Models\Merchant::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Fleet\Models\Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Fleet\Models\Driver::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Warehouses\Models\Warehouse::class);
    }

    protected static function booted(): void
    {
        static::saved(function (Shipment $shipment) {
            if ($shipment->wasChanged('state') && $shipment->state instanceof \App\Domains\Shipments\States\DeliveredState) {
                event(new \App\Domains\Shipments\Events\ShipmentDeliveredEvent($shipment));
            }
        });
    }
}
