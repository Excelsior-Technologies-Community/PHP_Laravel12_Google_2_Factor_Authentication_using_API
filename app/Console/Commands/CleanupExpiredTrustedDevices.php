<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrustedDevice;

class CleanupExpiredTrustedDevices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devices:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired trusted device records from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = TrustedDevice::where('expires_at', '<', now())->delete();

        $this->info("Successfully deleted {$deleted} expired trusted device records.");

        return Command::SUCCESS;
    }
}
