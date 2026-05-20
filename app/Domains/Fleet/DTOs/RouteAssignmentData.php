<?php

namespace App\Domains\Fleet\DTOs;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Data;

class RouteAssignmentData extends Data
{
    public function __construct(
        #[Exists('drivers', 'id')]
        public readonly int $driver_id,

        #[Date]
        public readonly string $dispatch_date,

        /** @var array<int> */
        #[ArrayType]
        public readonly array $shipment_ids,
    ) {}
}
