<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateTopTimePeriodsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $topPeriods = Order::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, SUM(total_price) as total_revenue")
            ->groupBy('period')
            ->orderBy('total_revenue', 'desc')
            ->take(3)
            ->get();

        Storage::put('reports/top_periods.json', $topPeriods->toJson());
    }
}
