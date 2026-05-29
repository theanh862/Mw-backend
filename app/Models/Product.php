<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'name_en',
        'price',
        'original_price',
        'image',
        'description',
        'description_en',
        'specs',
        'specs_en',
        'rating',
        'reviews_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'specs_en' => 'array',
            'price' => 'decimal:0',
            'original_price' => 'decimal:0',
            'rating' => 'decimal:1',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skus(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductReview::class);
    }
}

