<?php

namespace App\Services;

class PaymentService
{
    /**
     * Tạo URL thanh toán VNPay.
     *
     * @param string $orderId - Mã đơn hàng (ORD-XXXX)
     * @param float $amount - Số tiền thanh toán (VND)
     * @param string $orderInfo - Mô tả đơn hàng
     * @param string $ipAddress - IP của khách hàng
     * @return string - URL chuyển hướng tới VNPay
     */
    public function createVnpayPaymentUrl(string $orderId, float $amount, string $orderInfo, string $ipAddress): string
    {
        $vnpTmnCode = config('services.vnpay.tmn_code');
        $vnpHashSecret = config('services.vnpay.hash_secret');
        $vnpUrl = config('services.vnpay.url');
        $vnpReturnUrl = config('services.vnpay.return_url');

        $vnpTxnRef = $orderId;
        $vnpOrderInfo = $orderInfo;
        $vnpOrderType = 'billpayment';
        $vnpAmount = $amount * 100; // VNPay yêu cầu nhân 100
        $vnpLocale = 'vn';
        $vnpIpAddr = $ipAddress;

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnpTmnCode,
            "vnp_Amount" => $vnpAmount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnpIpAddr,
            "vnp_Locale" => $vnpLocale,
            "vnp_OrderInfo" => $vnpOrderInfo,
            "vnp_OrderType" => $vnpOrderType,
            "vnp_ReturnUrl" => $vnpReturnUrl,
            "vnp_TxnRef" => $vnpTxnRef,
        ];

        ksort($inputData);
        $hashdata = "";
        $query = "";
        $i = 0;

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnpHashSecret);

        // Build query string cùng cách encode để đảm bảo nhất quán
        $query = $hashdata . '&vnp_SecureHash=' . $vnpSecureHash;

        return $vnpUrl . "?" . $query;
    }

    /**
     * Xác minh chữ ký số từ VNPay khi redirect trở về.
     *
     * @param array $vnpData - Dữ liệu GET parameters từ VNPay
     * @return bool
     */
    public function verifyVnpayReturn(array $vnpData): bool
    {
        $vnpHashSecret = config('services.vnpay.hash_secret');
        $vnpSecureHash = $vnpData['vnp_SecureHash'] ?? '';

        // Bỏ các trường hash ra khỏi data để tính lại
        unset($vnpData['vnp_SecureHash']);
        unset($vnpData['vnp_SecureHashType']);

        ksort($vnpData);
        $hashData = "";
        $i = 0;

        foreach ($vnpData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnpHashSecret);

        return $secureHash === $vnpSecureHash;
    }
}
