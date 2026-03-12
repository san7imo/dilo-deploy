<?php

namespace App\Jobs;

use App\Services\SoftDeletePurger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PurgeSoftDeletedRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $days = 60,
        public int $batchSize = 200
    ) {
    }

    public function handle(SoftDeletePurger $purger): void
    {
        $days = max(1, $this->days);
        $threshold = now()->subDays($days);
        $summary = $purger->purgeOlderThan($threshold, $this->batchSize);

        Log::info('Soft-delete purge completed.', [
            'days' => $days,
            'batch_size' => $this->batchSize,
            ...$summary,
        ]);
    }
}
