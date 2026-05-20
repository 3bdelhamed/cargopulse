<?php

namespace App\Domains\Billing\Models;

use App\Domains\Shipments\Models\Shipment;
use App\Domains\Tenants\Models\Merchant;
use App\Domains\Tenants\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerTransaction extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
