<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class CleanupNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup {--days=30 : Number of days old to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notifications from the database';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $days = $this->option('days');
        
        $this->info("Cleaning up notifications older than {$days} days...");
        
        $deletedCount = $notificationService->cleanupOldNotifications($days);
        
        $this->info("Successfully deleted {$deletedCount} old notifications.");
        
        return Command::SUCCESS;
    }
} 