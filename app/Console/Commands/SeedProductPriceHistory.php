<?php

namespace App\Console\Commands;

use App\Services\ProductPriceHistoryService;
use Illuminate\Console\Command;

class SeedProductPriceHistory extends Command
{
    protected $signature = 'products:seed-price-history';
    protected $description = 'Start price tracking for existing products without inventing earlier history';

    public function handle(ProductPriceHistoryService $history): int
    {
        $count = $history->seedTrackingStarts();
        $this->info("Created {$count} price tracking start record(s).");

        return self::SUCCESS;
    }
}
