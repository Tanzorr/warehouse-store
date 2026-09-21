<?php

namespace App\Jobs;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateTopCategoriesReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $topCategories = OrderItem::selectRaw('products.category_id, categories.name, SUM(order_items.price * order_items.quantity) as total_revenue')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('products.category_id')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->with('product.category')
            ->get();

        Storage::put('reports/top_categories.json', $topCategories->toJson());
    }
}
