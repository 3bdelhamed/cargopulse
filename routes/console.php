<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Domains\Fleet\Jobs\PersistRedisGpsDataJob;
use App\Domains\Billing\Jobs\GenerateMerchantInvoicesJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PersistRedisGpsDataJob)->everyFiveMinutes();
Schedule::job(new GenerateMerchantInvoicesJob)->monthlyOn(1, '00:00');
