<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'search', 'sort_by', 'sort_dir']);
        $products = $this->productService->getAllProducts($filters);
        return response()->json($products);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $product = $this->productService->getProductById((int) $id);
            return response()->json($product);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm.'], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'specs' => 'nullable|array',
            'screen' => 'nullable|string',
            'cpu' => 'nullable|string',
            'ram' => 'nullable|string',
            'storage' => 'nullable|string',
            'battery' => 'nullable|string',
            'rating' => 'nullable|numeric|min:1|max:5',
            'reviews_count' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:255',
            'skus' => 'nullable|array',
            'skus.*.id' => 'nullable|integer',
            'skus.*.sku' => 'required|string|max:255',
            'skus.*.name' => 'required|string|max:255',
            'skus.*.price' => 'required|numeric|min:0',
            'skus.*.original_price' => 'nullable|numeric|min:0',
            'skus.*.stock' => 'nullable|integer|min:0',
            'skus.*.image' => 'nullable|string|max:255',
        ]);

        $product = $this->productService->createProduct($data);
        return response()->json($product, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_en' => 'nullable|string',
            'specs' => 'nullable|array',
            'screen' => 'nullable|string',
            'cpu' => 'nullable|string',
            'ram' => 'nullable|string',
            'storage' => 'nullable|string',
            'battery' => 'nullable|string',
            'rating' => 'nullable|numeric|min:1|max:5',
            'reviews_count' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:255',
            'skus' => 'nullable|array',
            'skus.*.id' => 'nullable|integer',
            'skus.*.sku' => 'required|string|max:255',
            'skus.*.name' => 'required|string|max:255',
            'skus.*.price' => 'required|numeric|min:0',
            'skus.*.original_price' => 'nullable|numeric|min:0',
            'skus.*.stock' => 'nullable|integer|min:0',
            'skus.*.image' => 'nullable|string|max:255',
        ]);

        try {
            $product = $this->productService->updateProduct((int) $id, $data);
            return response()->json($product);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm để cập nhật.'], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $success = $this->productService->deleteProduct((int) $id);
            if (!$success) {
                return response()->json(['message' => 'Xóa sản phẩm thất bại.'], 400);
            }
            return response()->json(['message' => 'Xóa sản phẩm thành công.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm.'], 404);
        }
    }
}
