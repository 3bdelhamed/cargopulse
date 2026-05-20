<?php

namespace App\Domains\Fleet\DTOs;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Data;

class ReorderRouteStopsData extends Data
{
    public function __construct(
        /** @var array<int> */
        #[ArrayType]
        public readonly array $shipment_ids,
    ) {}
}
