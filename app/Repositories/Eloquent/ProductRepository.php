<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\ProductRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    public function getAll(array $filters = [])
    {
        $query = Product::with(['category', 'skus']);

        // Lọc theo danh mục (slug)
        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        // Tìm kiếm theo tên sản phẩm (cả VI và EN)
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('name_en', 'like', "%{$term}%");
            });
        }

        // Sắp xếp
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->get();
    }

    public function findById(int $id)
    {
        return Product::with(['category', 'skus'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Product::create($data);
    }

    public function update(int $id, array $data)
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product->fresh('category');
    }

    public function delete(int $id)
    {
        return Product::findOrFail($id)->delete();
    }

    public function count()
    {
        return Product::count();
    }
}
