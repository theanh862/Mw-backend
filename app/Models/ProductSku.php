<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSku extends Model
{
    use HasFactory;

    protected $table = 'product_skus';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'name_en',
        'price',
        'original_price',
        'stock',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:0',
            'original_price' => 'decimal:0',
            'stock' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
