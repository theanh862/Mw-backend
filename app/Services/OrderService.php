<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Voucher;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\ProductRepositoryInterface;
use App\Services\NotificationService;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected ProductRepositoryInterface $productRepository,
        protected NotificationService $notificationService,
    ) {}

    public function getAllOrders()
    {
        return $this->orderRepository->getAll();
    }

    public function getOrdersByUser(int $userId)
    {
        return $this->orderRepository->getByUserId($userId);
    }

    public function getOrderById(string $id)
    {
        return $this->orderRepository->findById($id);
    }

    /**
     * Tạo đơn hàng mới từ dữ liệu checkout.
     *
     * @param array $data - Dữ liệu đơn hàng (customer_name, email, phone, address, payment_method, items)
     * @param int|null $userId - ID user nếu đã đăng nhập
     * @return \App\Models\Order
     */
    public function createOrder(array $data, ?int $userId = null)
    {
        // Tạo mã đơn hàng ngẫu nhiên dạng ORD-XXXX
        $orderId = 'ORD-' . str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        // Tính tổng tiền từ items
        $total = 0;
        $itemsData = [];

        foreach ($data['items'] as $item) {
            if (!empty($item['sku_id'])) {
                $sku = \App\Models\ProductSku::with('product')->findOrFail($item['sku_id']);
                $product = $sku->product;

                if ($sku->stock < $item['quantity']) {
                    throw new \Exception("Sản phẩm {$product->name} ({$sku->name}) chỉ còn {$sku->stock} sản phẩm trong kho.");
                }

                // Trừ tồn kho
                $sku->decrement('stock', $item['quantity']);

                $price = $sku->price;
                $subtotal = $price * $item['quantity'];
                $total += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_sku_id' => $sku->id,
                    'product_name' => $product->name . ' (' . $sku->name . ')',
                    'product_name_en' => ($product->name_en ?: $product->name) . ' (' . ($sku->name_en ?: $sku->name) . ')',
                    'product_image' => $sku->image ?: $product->image,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];
            } else {
                $product = $this->productRepository->findById($item['product_id'] ?? $item['id']);

                $price = $product->price;
                $subtotal = $price * $item['quantity'];
                $total += $subtotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_sku_id' => null,
                    'product_name' => $product->name,
                    'product_name_en' => $product->name_en ?: $product->name,
                    'product_image' => $product->image,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];
            }
        }

        // Áp dụng voucher nếu có
        $discountAmount = 0;
        $voucherCode = null;
        if (!empty($data['voucher_code'])) {
            $voucher = Voucher::where('code', strtoupper(trim($data['voucher_code'])))
                ->where('is_active', true)
                ->first();
            if ($voucher) {
                $discountAmount = $voucher->calcDiscount($total);
                $voucherCode = $voucher->code;
                $voucher->increment('used_count');
            }
        }

        $grandTotal = max(0, $total + ($data['shipping_fee'] ?? 0) - $discountAmount);

        // Tạo đơn hàng
        $order = $this->orderRepository->create([
            'id'              => $orderId,
            'user_id'         => $userId,
            'customer_name'   => $data['customer_name'],
            'email'           => $data['email'],
            'phone'           => $data['phone'],
            'address'         => $data['address'],
            'total'           => $grandTotal,
            'shipping_fee'    => $data['shipping_fee'] ?? 0,
            'discount_amount' => $discountAmount,
            'voucher_code'    => $voucherCode,
            'status'          => 'pending',
            'payment_method'  => $data['payment_method'] ?? 'cod',
            'payment_status'  => 'pending',
        ]);

        // Tạo chi tiết đơn hàng
        foreach ($itemsData as $itemData) {
            $itemData['order_id'] = $order->id;
            OrderItem::create($itemData);
        }

        if ($userId) {
            $this->notificationService->notifyOrderPlaced($userId, $order->id, (float) $order->total);
        }

        return $order->fresh('items');
    }

    public function updateOrderStatus(string $id, string $status)
    {
        $order = $this->orderRepository->findById($id);
        $updated = $this->orderRepository->update($id, ['status' => $status]);

        if ($order && $order->user_id) {
            $this->notificationService->notifyOrderStatus($order->user_id, $id, $status);
        }

        return $updated;
    }

    public function updatePaymentStatus(string $id, string $paymentStatus)
    {
        $data = ['payment_status' => $paymentStatus];
        if ($paymentStatus === 'paid') {
            $data['status'] = 'processing';
        }
        return $this->orderRepository->update($id, $data);
    }

    public function requestReturn(string $id, int $userId, string $reason)
    {
        $order = $this->orderRepository->findById($id);

        if (!$order || $order->user_id !== $userId) {
            throw new \Exception('Không tìm thấy đơn hàng.');
        }
        if ($order->status !== 'completed') {
            throw new \Exception('Chỉ có thể yêu cầu trả hàng cho đơn hàng đã hoàn thành.');
        }
        if ($order->return_status !== null) {
            throw new \Exception('Đơn hàng này đã có yêu cầu trả hàng.');
        }

        $updated = $this->orderRepository->update($id, [
            'return_status' => 'requested',
            'return_reason' => $reason,
        ]);

        $this->notificationService->notifyReturnRequested($id, $userId);

        return $updated;
    }

    public function processReturn(string $id, string $action, ?string $adminNote = null)
    {
        $order = $this->orderRepository->findById($id);

        if (!$order || $order->return_status !== 'requested') {
            throw new \Exception('Không tìm thấy yêu cầu trả hàng hợp lệ.');
        }

        if ($action === 'approve') {
            // Hoàn kho
            foreach ($order->items as $item) {
                if ($item->product_sku_id) {
                    \App\Models\ProductSku::where('id', $item->product_sku_id)
                        ->increment('stock', $item->quantity);
                }
            }

            $updated = $this->orderRepository->update($id, [
                'return_status' => 'approved',
                'refund_status' => 'processing',
                'refund_amount' => $order->total,
                'admin_note'    => $adminNote,
            ]);

            if ($order->user_id) {
                $this->notificationService->notifyReturnProcessed($order->user_id, $id, 'approve', $adminNote);
            }

            return $updated;
        }

        $updated = $this->orderRepository->update($id, [
            'return_status' => 'rejected',
            'admin_note'    => $adminNote,
        ]);

        if ($order->user_id) {
            $this->notificationService->notifyReturnProcessed($order->user_id, $id, 'reject', $adminNote);
        }

        return $updated;
    }

    public function completeRefund(string $id)
    {
        $order = $this->orderRepository->findById($id);

        if (!$order || $order->refund_status !== 'processing') {
            throw new \Exception('Không tìm thấy đơn hàng đang chờ hoàn tiền.');
        }

        $updated = $this->orderRepository->update($id, [
            'refund_status' => 'refunded',
        ]);

        if ($order->user_id) {
            $this->notificationService->notifyRefundCompleted($order->user_id, $id, (float) $order->total);
        }

        return $updated;
    }

    public function getOrderCount()
    {
        return $this->orderRepository->count();
    }

    public function getTotalRevenue()
    {
        return $this->orderRepository->totalRevenue();
    }
}
