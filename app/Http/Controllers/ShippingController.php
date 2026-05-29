<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingController extends Controller
{
    private string $baseUrl;
    private string $token;
    private int $shopId;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn'), '/');
        $this->token   = env('GHN_TOKEN', '');
        $this->shopId  = (int) env('GHN_SHOP_ID', 0);
    }

    private function headers(): array
    {
        return [
            'Token'        => $this->token,
            'ShopId'       => $this->shopId,
            'Content-Type' => 'application/json',
        ];
    }

    public function provinces()
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Token' => $this->token, 'Content-Type' => 'application/json'])
                ->get("{$this->baseUrl}/shiip/public-api/master-data/province");

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('GHN provinces error: ' . $e->getMessage());
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function districts(Request $request)
    {
        $request->validate(['province_id' => 'required|integer']);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Token' => $this->token, 'Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/shiip/public-api/master-data/district", [
                    'province_id' => (int) $request->province_id,
                ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('GHN districts error: ' . $e->getMessage());
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function wards(Request $request)
    {
        $request->validate(['district_id' => 'required|integer']);

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Token' => $this->token, 'Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/shiip/public-api/master-data/ward", [
                    'district_id' => (int) $request->district_id,
                ]);

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('GHN wards error: ' . $e->getMessage());
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    public function calculateFee(Request $request)
    {
        $request->validate([
            'to_district_id' => 'required|integer',
            'to_ward_code'   => 'required|string',
            'weight'         => 'nullable|integer',
            'insurance_value'=> 'nullable|integer',
        ]);

        try {
            $payload = [
                'service_type_id' => 2, // E-Commerce Delivery
                'to_district_id'  => (int) $request->to_district_id,
                'to_ward_code'    => (string) $request->to_ward_code,
                'weight'          => max(1, (int) ($request->weight ?? 500)),
                'insurance_value' => (int) ($request->insurance_value ?? 0),
            ];

            $response = Http::timeout(10)
                ->withHeaders($this->headers())
                ->post("{$this->baseUrl}/shiip/public-api/v2/shipping-order/fee", $payload);

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('GHN fee error: ' . $e->getMessage());
            return response()->json(['code' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}
