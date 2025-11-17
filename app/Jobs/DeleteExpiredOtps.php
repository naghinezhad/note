<?php

namespace App\Jobs;

use App\Models\Otp;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeleteExpiredOtps
{
    use Dispatchable, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Otp::where('expires_at', '<', now())->delete();
    }
}
