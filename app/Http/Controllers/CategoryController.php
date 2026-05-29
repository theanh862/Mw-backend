<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = $this->categoryService->getAllCategories();
        return response()->json($categories);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $category = $this->categoryService->getCategoryById((int) $id);
        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục.'], 404);
        }
        return response()->json($category);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'icon' => 'nullable|string|max:255',
        ]);

        $category = $this->categoryService->createCategory($data);
        return response()->json($category, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $id,
            'icon' => 'nullable|string|max:255',
        ]);

        $category = $this->categoryService->updateCategory((int) $id, $data);
        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $success = $this->categoryService->deleteCategory((int) $id);
        if (!$success) {
            return response()->json(['message' => 'Xóa danh mục thất bại hoặc không tìm thấy.'], 400);
        }
        return response()->json(['message' => 'Xóa danh mục thành công.']);
    }
}
