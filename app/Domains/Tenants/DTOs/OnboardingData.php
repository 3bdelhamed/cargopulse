<?php

namespace App\Domains\Tenants\DTOs;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class OnboardingData extends Data
{
    public function __construct(
        #[StringType, Min(3)]
        public readonly string $company_name,

        #[StringType, Min(3)]
        public readonly string $admin_name,

        #[Email]
        public readonly string $admin_email,

        #[StringType, Min(8)]
        public readonly string $password,
    ) {}
}
