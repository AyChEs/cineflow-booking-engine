<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SeatLock;
use Illuminate\Support\Facades\Log;

class CleanupExpiredSeatLocks extends Command
{
    protected $signature = 'cleanup:seat-locks';

    protected $description = 'Remove expired seat lock reservations from the database';

    public function handle(): int
    {
        try {
            $deleted = SeatLock::where('expires_at', '<', now())->delete();
            
            Log::info('Seat lock cleanup executed', [
                'deleted_locks' => $deleted,
                'timestamp' => now(),
            ]);
            
            if ($this->output->isVerbose()) {
                $this->info("✓ Deleted {$deleted} expired seat locks");
            }
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Seat lock cleanup failed', [
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);
            
            $this->error("✗ Cleanup failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
