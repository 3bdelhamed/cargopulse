<?php

namespace App\Domains\Shipments\DTOs;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ShipmentData extends Data
{
    public function __construct(
        #[Exists('merchants', 'id')]
        public readonly int $merchant_id,

        #[StringType]
        public readonly string $tracking_number,

        #[StringType]
        public readonly string $destination_address,

        #[Numeric, Min(0)]
        public readonly float|int $cod_amount,
    ) {
    }
}
