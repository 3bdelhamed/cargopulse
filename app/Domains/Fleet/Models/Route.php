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

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return list<string>
     */
    public static function activeStatuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_IN_PROGRESS];
    }

    /**
     * @return list<string>
     */
    public static function terminalStatuses(): array
    {
        return [self::STATUS_COMPLETED, self::STATUS_ARCHIVED, self::STATUS_CANCELLED];
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class)->orderBy('route_sequence', 'asc');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
