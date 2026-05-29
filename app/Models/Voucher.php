<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'min_order_value',
        'max_discount', 'usage_limit', 'used_count', 'is_active', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'value'           => 'decimal:0',
            'min_order_value' => 'decimal:0',
            'max_discount'    => 'decimal:0',
            'is_active'       => 'boolean',
            'expires_at'      => 'datetime',
        ];
    }

    public function calcDiscount(float $subtotal): float
    {
        if ($this->type === 'percent') {
            $discount = $subtotal * ($this->value / 100);
            if ($this->max_discount) {
                $discount = min($discount, (float) $this->max_discount);
            }
            return $discount;
        }
        return min((float) $this->value, $subtotal);
    }
}
