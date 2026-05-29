<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Helper to format an order model to match frontend's expected camelCase structure.
     */
    private function formatOrder($order)
    {
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'id' => $item->id,
                'productId' => $item->product_id,
                'name' => $item->product_name,
                'name_en' => $item->product_name_en,
                'image' => $item->product_image,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
            ];
        }

        return [
            'id' => $order->id,
            'userId' => $order->user_id,
            'customerName' => $order->customer_name,
            'email' => $order->email,
            'phone' => $order->phone,
            'address' => $order->address,
            'total' => (float) $order->total,
            'shippingFee' => (float) ($order->shipping_fee ?? 0),
            'status' => $order->status,
            'paymentMethod' => $order->payment_method,
            'paymentStatus' => $order->payment_status,
            'voucherCode'    => $order->voucher_code,
            'discountAmount' => (float) ($order->discount_amount ?? 0),
            'returnStatus' => $order->return_status,
            'returnReason' => $order->return_reason,
            'adminNote' => $order->admin_note,
            'refundStatus' => $order->refund_status,
            'refundAmount' => $order->refund_amount ? (float) $order->refund_amount : null,
            'createdAt' => $order->created_at->toIso8601String(),
            'items' => $items,
        ];
    }

    /**
     * Display a listing of orders for the authenticated customer.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = $this->orderService->getOrdersByUser($user->id);
        $formatted = $orders->map(fn($o) => $this->formatOrder($o));
        return response()->json($formatted);
    }

    /**
     * Display all orders for admin/staff management.
     */
    public function adminIndex(Request $request)
    {
        $orders = $this->orderService->getAllOrders();
        $formatted = $orders->map(fn($o) => $this->formatOrder($o));
        return response()->json($formatted);
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, $id)
    {
        $order = $this->orderService->getOrderById($id);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        }

        $user = $request->user();
        if (!$user->isAdminOrStaff() && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền xem đơn hàng này.'], 403);
        }

        return response()->json($this->formatOrder($order));
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customerName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'shippingFee' => 'nullable|numeric|min:0',
            'voucherCode' => 'nullable|string|max:50',
            'paymentMethod' => 'required|string|in:cod,vnpay',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.sku_id' => 'nullable|exists:product_skus,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // Map items to standard backend structure (ensuring product_id is populated)
        $mappedItems = [];
        foreach ($data['items'] as $item) {
            $productId = $item['product_id'] ?? $item['id'] ?? null;
            if (!$productId) {
                return response()->json([
                    'message' => 'Lỗi tạo đơn hàng: product_id hoặc id là bắt buộc cho mỗi item.'
                ], 422);
            }
            $mappedItems[] = [
                'product_id' => (int) $productId,
                'sku_id' => isset($item['sku_id']) ? (int) $item['sku_id'] : null,
                'quantity' => (int) $item['quantity'],
            ];
        }

        // Map camelCase to snake_case for the service
        $serviceData = [
            'customer_name' => $data['customerName'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'shipping_fee' => (float) ($data['shippingFee'] ?? 0),
            'voucher_code' => $data['voucherCode'] ?? null,
            'payment_method' => $data['paymentMethod'],
            'items' => $mappedItems,
        ];

        try {
            $userId = $request->user() ? $request->user()->id : null;
            $order = $this->orderService->createOrder($serviceData, $userId);

            return response()->json($this->formatOrder($order), 201);
        } catch (\Exception $e) {
            Log::error('Create order error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Lỗi tạo đơn hàng: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order status (Admin only).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $order = $this->orderService->updateOrderStatus($id, $request->input('status'));
        if (!$order) {
            return response()->json(['message' => 'Cập nhật trạng thái thất bại.'], 400);
        }

        return response()->json($this->formatOrder($order));
    }

    /**
     * Update payment status (Admin only).
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'paymentStatus' => 'required|string|in:pending,paid',
        ]);

        $order = $this->orderService->updatePaymentStatus($id, $request->input('paymentStatus'));
        if (!$order) {
            return response()->json(['message' => 'Cập nhật trạng thái thanh toán thất bại.'], 400);
        }

        return response()->json($this->formatOrder($order));
    }

    /**
     * Customer requests a return for a completed order.
     */
    public function requestReturn(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:500',
        ]);

        try {
            $order = $this->orderService->requestReturn($id, $request->user()->id, $request->input('reason'));
            return response()->json($this->formatOrder($order));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Admin approves or rejects a return request.
     */
    public function processReturn(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|string|in:approve,reject',
            'adminNote' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->orderService->processReturn(
                $id,
                $request->input('action'),
                $request->input('adminNote')
            );
            return response()->json($this->formatOrder($order));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Admin marks refund as completed.
     */
    public function completeRefund(Request $request, $id)
    {
        try {
            $order = $this->orderService->completeRefund($id);
            return response()->json($this->formatOrder($order));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
