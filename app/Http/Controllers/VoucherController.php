<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VoucherController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate([
            'code'     => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $voucher = Voucher::where('code', strtoupper(trim($request->code)))->first();

        if (!$voucher || !$voucher->is_active) {
            return response()->json(['message' => 'Mã giảm giá không tồn tại hoặc đã bị vô hiệu hóa.'], 422);
        }
        if ($voucher->expires_at && $voucher->expires_at->isPast()) {
            return response()->json(['message' => 'Mã giảm giá đã hết hạn.'], 422);
        }
        if ($voucher->usage_limit && $voucher->used_count >= $voucher->usage_limit) {
            return response()->json(['message' => 'Mã giảm giá đã hết lượt sử dụng.'], 422);
        }
        if ($request->subtotal < $voucher->min_order_value) {
            return response()->json([
                'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($voucher->min_order_value, 0, ',', '.') . 'đ để dùng mã này.',
            ], 422);
        }

        $discount = $voucher->calcDiscount((float) $request->subtotal);

        return response()->json([
            'code'           => $voucher->code,
            'type'           => $voucher->type,
            'value'          => (float) $voucher->value,
            'discountAmount' => $discount,
            'description'    => $this->description($voucher),
        ]);
    }

    // ── Admin CRUD ────────────────────────────────────────────────────

    public function index()
    {
        return response()->json(Voucher::orderByDesc('created_at')->get()->map(fn($v) => $this->format($v)));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'            => 'required|string|max:50|unique:vouchers,code',
            'type'            => 'required|in:percent,fixed',
            'value'           => 'required|numeric|min:1',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount'    => 'nullable|numeric|min:0',
            'usage_limit'     => 'nullable|integer|min:1',
            'is_active'       => 'boolean',
            'expires_at'      => 'nullable|date',
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $voucher = Voucher::create($data);
        return response()->json($this->format($voucher), 201);
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        $data = $request->validate([
            'code'            => 'sometimes|string|max:50|unique:vouchers,code,' . $id,
            'type'            => 'sometimes|in:percent,fixed',
            'value'           => 'sometimes|numeric|min:1',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount'    => 'nullable|numeric|min:0',
            'usage_limit'     => 'nullable|integer|min:1',
            'is_active'       => 'boolean',
            'expires_at'      => 'nullable|date',
        ]);

        if (isset($data['code'])) $data['code'] = strtoupper(trim($data['code']));
        $voucher->update($data);
        return response()->json($this->format($voucher));
    }

    public function destroy($id)
    {
        Voucher::findOrFail($id)->delete();
        return response()->json(['message' => 'Đã xóa voucher.']);
    }

    private function description(Voucher $v): string
    {
        if ($v->type === 'percent') {
            $desc = "Giảm {$v->value}%";
            if ($v->max_discount) $desc .= ' (tối đa ' . number_format($v->max_discount, 0, ',', '.') . 'đ)';
        } else {
            $desc = 'Giảm ' . number_format($v->value, 0, ',', '.') . 'đ';
        }
        if ($v->min_order_value > 0) $desc .= ' cho đơn từ ' . number_format($v->min_order_value, 0, ',', '.') . 'đ';
        return $desc;
    }

    private function format(Voucher $v): array
    {
        return [
            'id'             => $v->id,
            'code'           => $v->code,
            'type'           => $v->type,
            'value'          => (float) $v->value,
            'minOrderValue'  => (float) $v->min_order_value,
            'maxDiscount'    => $v->max_discount ? (float) $v->max_discount : null,
            'usageLimit'     => $v->usage_limit,
            'usedCount'      => $v->used_count,
            'isActive'       => $v->is_active,
            'expiresAt'      => $v->expires_at?->toDateString(),
            'description'    => $this->description($v),
            'createdAt'      => $v->created_at->toDateString(),
        ];
    }
}
