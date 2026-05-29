<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Services\OrderService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Lấy dữ liệu thống kê tổng quan cho Admin Dashboard.
     */
    public function dashboardStats()
    {
        $totalRevenue = (float) $this->orderService->getTotalRevenue();
        $totalOrders = $this->orderService->getOrderCount();
        $pendingOrders = \App\Models\Order::where('status', 'pending')->count();
        
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        
        // Đếm số lượng khách hàng (User có role 'customer')
        $totalCustomers = User::where('role', 'customer')->count();

        // Đơn hàng gần đây (5 đơn hàng mới nhất)
        $recentOrders = \App\Models\Order::with('items')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'customerName' => $order->customer_name,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'paymentStatus' => $order->payment_status,
                    'createdAt' => $order->created_at->toIso8601String(),
                ];
            });

        // Tỷ lệ ngành hàng (đếm số lượng sản phẩm theo từng danh mục)
        $categories = Category::withCount('products')->get();
        $categoryBreakdown = $categories->map(function ($cat) use ($totalProducts) {
            $count = $cat->products_count;
            $percentage = $totalProducts > 0 ? round(($count / $totalProducts) * 100) : 0;
            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'count' => $count,
                'percentage' => $percentage,
            ];
        });

        // Orders by Status
        $ordersByStatus = \App\Models\Order::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $ordersByStatusFormatted = [];
        $statusLabels = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        foreach ($statuses as $status) {
            $ordersByStatusFormatted[] = [
                'status' => $status,
                'name' => $statusLabels[$status],
                'value' => $ordersByStatus[$status] ?? 0,
            ];
        }

        // Revenue by Month (last 6 months)
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $ordersLast6Months = \App\Models\Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->get();

        $revenueByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $monthLabel = 'T' . $monthDate->format('m/y');

            $monthlyTotal = $ordersLast6Months->filter(function ($order) use ($monthKey) {
                return $order->created_at->format('Y-m') === $monthKey;
            })->sum('total');

            $revenueByMonth[] = [
                'month' => $monthKey,
                'name' => $monthLabel,
                'revenue' => (float) $monthlyTotal,
            ];
        }

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalCustomers' => $totalCustomers,
            'recentOrders' => $recentOrders,
            'categoryBreakdown' => $categoryBreakdown,
            'ordersByStatus' => $ordersByStatusFormatted,
            'revenueByMonth' => $revenueByMonth,
        ]);
    }

    /**
     * Lấy danh sách khách hàng (Admin only).
     */
    public function customersList()
    {
        $customers = User::where('role', 'customer')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                // Đếm số đơn hàng đã đặt
                $ordersCount = $user->orders()->count();
                $spent = (float) $user->orders()->where('payment_status', 'paid')->sum('total');

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'createdAt' => $user->created_at->toIso8601String(),
                    'ordersCount' => $ordersCount,
                    'totalSpent' => $spent,
                ];
            });

        return response()->json($customers);
    }
}
