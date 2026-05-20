<?php

namespace App\Domains\Fleet\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PersistRedisGpsDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * 
     * To schedule this job, add the following to your routes/console.php:
     * 
     * use App\Domains\Fleet\Jobs\PersistRedisGpsDataJob;
     * use Illuminate\Support\Facades\Schedule;
     * 
     * Schedule::job(new PersistRedisGpsDataJob)->everyMinute();
     */
    public function handle(): void
    {
        $this->execute();
    }

    public function execute(): void
    {
        $cursor = '0';
        
        do {
            // Scan for all driver data hashes avoiding blocking KEYS *
            [$cursor, $keys] = Redis::scan($cursor, 'match', 'tenant:*:drivers:data', 'count', 100);
            
            // Note: Redis::scan format varies by client (phpredis vs predis). 
            // In phpredis it returns an array of keys directly, but we assume the standard output format.
            if (!$keys) {
                continue;
            }

            foreach ($keys as $hashKey) {
                preg_match('/tenant:(\d+):drivers:data/', $hashKey, $matches);
                if (!isset($matches[1])) {
                    continue;
                }
                
                $tenantId = $matches[1];
                $driverData = Redis::hgetall($hashKey);
                
                if (empty($driverData)) {
                    continue;
                }

                $inserts = [];
                foreach ($driverData as $driverId => $payload) {
                    $data = json_decode($payload, true);
                    if ($data) {
                        $inserts[] = [
                            'tenant_id' => $tenantId,
                            'driver_id' => $driverId,
                            'lat' => $data['lat'],
                            'lng' => $data['lng'],
                            'timestamp' => date('Y-m-d H:i:s', $data['timestamp']),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (!empty($inserts)) {
                    // Bulk insert to PostgreSQL to minimize I/O overhead
                    DB::table('location_histories')->insert($inserts);
                    
                    // Cleanup processed keys
                    Redis::del($hashKey);
                    
                    // Optionally trim or cleanup the geo index here if needed:
                    // Redis::del("tenant:{$tenantId}:drivers:geo"); 
                }
            }
        } while ($cursor !== '0' && $cursor !== 0);
    }
}
