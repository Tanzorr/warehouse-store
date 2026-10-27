<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateTopUsersReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $topUsers = Order::selectRaw('user_id, name, SUM(total_price) as total_revenue')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->groupBy('user_id')
            ->orderBy('total_revenue', 'desc')
            ->take(5)
            ->with('user')
            ->get();

        Storage::put('reports/top_users.json', $topUsers->toJson());
    }
}
