<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Clean up old rate limit records
        $schedule->command('cache:prune-stale-tags')->hourly();
        
        // Reset daily metrics at midnight
        $schedule->command('metrics:reset-daily')->daily();
        
        // Auto-release tables 1 hour after reservation time (runs every 5 minutes)
        $schedule->command('tables:auto-release')->everyFiveMinutes();
        
        // Laravel Pulse: Process ingested data every minute
        $schedule->command('pulse:ingest')->everyMinute()->withoutOverlapping();
        
        // Laravel Pulse: Trim old data daily
        $schedule->command('pulse:trim')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

