<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VNPayController extends Controller
{
    protected $orderService;
    protected $paymentService;

    public function __construct(OrderService $orderService, PaymentService $paymentService)
    {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
    }

    /**
     * Tạo đường dẫn thanh toán VNPay cho đơn hàng.
     */
    public function createPaymentUrl(Request $request)
    {
        $request->validate([
            'orderId' => 'required|string|exists:orders,id',
        ]);

        $orderId = $request->input('orderId');
        $order = $this->orderService->getOrderById($orderId);

        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng.'], 404);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Đơn hàng này đã được thanh toán trước đó.'], 400);
        }

        try {
            $ipAddress = $request->ip() ?? '127.0.0.1';
            $paymentUrl = $this->paymentService->createVnpayPaymentUrl(
                $order->id,
                (float) $order->total,
                'Thanh toán đơn hàng ' . $order->id,
                $ipAddress
            );

            return response()->json([
                'paymentUrl' => $paymentUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('VNPay payment url generation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Lỗi khi tạo liên kết thanh toán.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Callback nhận kết quả thanh toán từ VNPay.
     */
    public function paymentReturn(Request $request)
    {
        $vnpData = $request->all();
        
        Log::info('VNPay Payment Return Payload: ', $vnpData);

        $isValid = $this->paymentService->verifyVnpayReturn($vnpData);
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        $orderId = $vnpData['vnp_TxnRef'] ?? '';
        // vnp_Amount từ VNPay có đơn vị là xu (đã nhân 100), chia 100 để có giá trị thực tế
        $amount = isset($vnpData['vnp_Amount']) ? ($vnpData['vnp_Amount'] / 100) : 0;
        $responseCode = $vnpData['vnp_ResponseCode'] ?? '99';

        if (!$isValid) {
            Log::warning('VNPay signature verification failed for Order ' . $orderId);
            return redirect($frontendUrl . '/vnpay-simulator?orderId=' . $orderId . '&amount=' . $amount . '&vnp_ResponseCode=97');
        }

        try {
            if ($responseCode === '00') {
                // Thanh toán thành công, cập nhật trạng thái
                $this->orderService->updatePaymentStatus($orderId, 'paid');
                Log::info('Order ' . $orderId . ' payment updated to PAID via VNPay.');
            } else {
                Log::info('Order ' . $orderId . ' payment failed/cancelled with code ' . $responseCode);
            }

            return redirect($frontendUrl . '/vnpay-simulator?orderId=' . $orderId . '&amount=' . $amount . '&vnp_ResponseCode=' . $responseCode);
        } catch (\Exception $e) {
            Log::error('Error processing VNPay return: ' . $e->getMessage());
            return redirect($frontendUrl . '/vnpay-simulator?orderId=' . $orderId . '&amount=' . $amount . '&vnp_ResponseCode=99');
        }
    }
}
