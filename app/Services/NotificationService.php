<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function notifyOrderPlaced(int $userId, string $orderId, float $total): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => 'order_placed',
            'title'   => 'Đặt hàng thành công',
            'message' => "Đơn hàng {$orderId} đã được đặt thành công. Tổng tiền: " . number_format($total, 0, ',', '.') . 'đ',
            'data'    => ['order_id' => $orderId],
        ]);

        // Notify all admins/staff
        $this->notifyAdmins('order_placed', 'Có đơn hàng mới', "Đơn hàng {$orderId} vừa được đặt.", ['order_id' => $orderId]);
    }

    public function notifyOrderStatus(int $userId, string $orderId, string $status): void
    {
        $labels = [
            'processing' => ['title' => 'Đơn hàng đang được xử lý', 'message' => "Đơn hàng {$orderId} đang được chuẩn bị và vận chuyển."],
            'completed'  => ['title' => 'Đơn hàng đã giao thành công', 'message' => "Đơn hàng {$orderId} đã được giao thành công. Cảm ơn bạn đã mua sắm!"],
            'cancelled'  => ['title' => 'Đơn hàng đã bị hủy', 'message' => "Đơn hàng {$orderId} đã bị hủy."],
        ];

        if (!isset($labels[$status])) return;

        Notification::create([
            'user_id' => $userId,
            'type'    => 'order_status',
            'title'   => $labels[$status]['title'],
            'message' => $labels[$status]['message'],
            'data'    => ['order_id' => $orderId, 'status' => $status],
        ]);
    }

    public function notifyReturnRequested(string $orderId, int $customerId): void
    {
        // Notify admins/staff
        $this->notifyAdmins('return_requested', 'Có yêu cầu trả hàng', "Khách hàng yêu cầu trả hàng cho đơn {$orderId}.", ['order_id' => $orderId]);

        // Confirm to customer
        Notification::create([
            'user_id' => $customerId,
            'type'    => 'return_requested',
            'title'   => 'Yêu cầu trả hàng đã được gửi',
            'message' => "Yêu cầu trả hàng cho đơn {$orderId} đã được gửi. Chúng tôi sẽ xem xét và phản hồi sớm nhất.",
            'data'    => ['order_id' => $orderId],
        ]);
    }

    public function notifyReturnProcessed(int $userId, string $orderId, string $action, ?string $adminNote): void
    {
        if ($action === 'approve') {
            Notification::create([
                'user_id' => $userId,
                'type'    => 'return_processed',
                'title'   => 'Yêu cầu trả hàng được duyệt',
                'message' => "Đơn hàng {$orderId} đã được chấp nhận trả hàng. Hoàn tiền đang được xử lý." . ($adminNote ? " Ghi chú: {$adminNote}" : ''),
                'data'    => ['order_id' => $orderId, 'action' => 'approved'],
            ]);
        } else {
            Notification::create([
                'user_id' => $userId,
                'type'    => 'return_processed',
                'title'   => 'Yêu cầu trả hàng bị từ chối',
                'message' => "Yêu cầu trả hàng cho đơn {$orderId} đã bị từ chối." . ($adminNote ? " Lý do: {$adminNote}" : ''),
                'data'    => ['order_id' => $orderId, 'action' => 'rejected'],
            ]);
        }
    }

    public function notifyRefundCompleted(int $userId, string $orderId, float $amount): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => 'refund_completed',
            'title'   => 'Hoàn tiền thành công',
            'message' => "Đơn hàng {$orderId} đã được hoàn tiền " . number_format($amount, 0, ',', '.') . 'đ thành công.',
            'data'    => ['order_id' => $orderId, 'amount' => $amount],
        ]);
    }

    private function notifyAdmins(string $type, string $title, string $message, array $data = []): void
    {
        $admins = User::whereIn('role', ['admin', 'staff'])->pluck('id');
        foreach ($admins as $adminId) {
            Notification::create([
                'user_id'  => $adminId,
                'type'     => $type,
                'audience' => 'admin',
                'title'    => $title,
                'message'  => $message,
                'data'     => $data,
            ]);
        }
    }
}
