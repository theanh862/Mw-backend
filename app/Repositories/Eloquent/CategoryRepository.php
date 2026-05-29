<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\CategoryRepositoryInterface;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAll()
    {
        return Category::withCount('products')->orderBy('name')->get();
    }

    public function findById(int $id)
    {
        return Category::findOrFail($id);
    }

    public function findBySlug(string $slug)
    {
        return Category::where('slug', $slug)->firstOrFail();
    }

    public function create(array $data)
    {
        return Category::create($data);
    }

    public function update(int $id, array $data)
    {
        $category = Category::findOrFail($id);
        $category->update($data);
        return $category;
    }

    public function delete(int $id)
    {
        return Category::findOrFail($id)->delete();
    }
}
