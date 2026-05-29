<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Khóa chính kiểu string (ORD-XXXX), không auto-increment
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'customer_name',
        'email',
        'phone',
        'address',
        'district_id',
        'ward_code',
        'total',
        'shipping_fee',
        'status',
        'payment_method',
        'payment_status',
        'return_status',
        'return_reason',
        'admin_note',
        'refund_status',
        'refund_amount',
        'voucher_code',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'total'           => 'decimal:0',
            'shipping_fee'    => 'decimal:0',
            'refund_amount'   => 'decimal:0',
            'discount_amount' => 'decimal:0',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
