<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetDailyMetrics extends Command
{
    protected $signature = 'metrics:reset-daily';
    protected $description = 'Reset daily metrics counters';

    public function handle(): int
    {
        Cache::forget('queue_processed_today');
        Cache::forget('queue_failed_today');
        
        $this->info('Daily metrics reset successfully.');
        
        return Command::SUCCESS;
    }
}

