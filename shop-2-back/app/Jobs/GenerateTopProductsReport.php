<?php

namespace App\Jobs;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateTopProductsReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $topProducts = OrderItem::selectRaw('product_id, name, SUM(price * quantity) as total_revenue ')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->with('product')
            ->get();

        Storage::put('reports/top_products.json', $topProducts->toJson());
    }
}
