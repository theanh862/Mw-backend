<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Get reviews for a specific product.
     */
    public function index($productId)
    {
        $product = Product::findOrFail($productId);
        
        $reviews = $product->reviews()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reviews);
    }

    /**
     * Store a new product review.
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'content' => 'required|string|min:3',
        ]);

        $product = Product::findOrFail($productId);
        $user = $request->user();

        // Check if user already reviewed this product to avoid duplicate reviews if desired
        // Or simply allow them to review. Let's allow them, but maybe just one review per product or multiple.
        // Usually, one review per product is clean, but let's just create it.
        
        $review = DB::transaction(function () use ($product, $user, $request) {
            $newReview = ProductReview::create([
                'product_id' => $product->id,
                'user_id'    => $user->id,
                'user_name'  => $user->name,
                'rating'     => $request->rating,
                'content'    => $request->content,
            ]);

            // Recalculate average rating and reviews count
            $reviewsCount = $product->reviews()->count();
            $avgRating = $product->reviews()->avg('rating');

            $product->update([
                'rating' => round($avgRating, 1),
                'reviews_count' => $reviewsCount,
            ]);

            return $newReview;
        });

        return response()->json([
            'message' => 'Đánh giá sản phẩm thành công!',
            'review' => $review,
        ], 201);
    }
}
