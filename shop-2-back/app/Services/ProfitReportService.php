<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\DB;

class ProfitReportService
{
    public function getTotalProfit(array $dataParams)
    {
        $query = OrderItem::selectRaw('SUM((order_items.price - products.purchase_price) * order_items.quantity) as total_profit')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->when($dataParams['start_date'] && $dataParams['end_date'], function ($query) use ($dataParams) {
                $query->whereBetween('order_items.created_at', [$dataParams['start_date'], $dataParams['end_date']]);
            })
            ->when($dataParams['category_id'], function ($query) use ($dataParams) {
                $query->where('products.category_id', $dataParams['category_id']);
            })
            ->when($dataParams['product_id'], function ($query) use ($dataParams) {
                $query->where('order_items.product_id', $dataParams['product_id']);
            });

        return $query->first();
    }

    public function getProfitByCategories(array $dataParams): array
    {
        $query = OrderItem::select(
            'categories.id as category_id',
            'categories.name as category_name',
            DB::raw('SUM((order_items.price - products.purchase_price) * order_items.quantity) as category_profit')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name')
            ->when($dataParams['start_date'] && $dataParams['end_date'], function ($query) use ($dataParams) {
                $query->whereBetween('order_items.created_at', [$dataParams['start_date'], $dataParams['end_date']]);
            });

        return $query->get()->all();
    }

    public function getAnnualUsersReport(
        User $receiver,
        DateTime $startDate,
        DateTime $endDate,
        ?array $categories = null
    ): array {
        $query = Order::where('user_id', $receiver->id)
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->when($categories, function ($query, $categories) {
                $query->whereIn('products.category_id', $categories);
            });

        $totalRevenue = $query->sum('total_price');
        $totalOrders = $query->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $monthlyRevenue = $query->selectRaw('YEAR(orders.created_at) as year, MONTH(orders.created_at) as month, SUM(total_price) as revenue')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order_value' => $averageOrderValue,
            'monthly_revenue' => $monthlyRevenue,
            '$receiver' => $receiver,
        ];
    }
}
