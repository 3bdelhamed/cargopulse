<?php

namespace App\Domains\Warehouses\DTOs;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ScanData extends Data
{
    public function __construct(
        #[StringType]
        public readonly string $tracking_number,

        #[Exists('warehouses', 'id')]
        public readonly int $warehouse_id,
    ) {}
}
