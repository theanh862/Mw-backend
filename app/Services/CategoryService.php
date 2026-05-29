<?php

namespace App\Services;

use App\Repositories\CategoryRepositoryInterface;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository,
        protected TranslationService $translationService,
    ) {}

    public function getAllCategories()
    {
        return $this->categoryRepository->getAll();
    }

    public function getCategoryById(int $id)
    {
        return $this->categoryRepository->findById($id);
    }

    public function createCategory(array $data)
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        if (empty($data['name_en']) && !empty($data['name'])) {
            $data['name_en'] = $this->translationService->translate($data['name']) ?? $data['name'];
        }
        return $this->categoryRepository->create($data);
    }

    public function updateCategory(int $id, array $data)
    {
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->categoryRepository->update($id, $data);
    }

    public function deleteCategory(int $id)
    {
        return $this->categoryRepository->delete($id);
    }
}
