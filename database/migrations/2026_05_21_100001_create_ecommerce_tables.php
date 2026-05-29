<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng danh mục sản phẩm
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        // Bảng sản phẩm
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 15, 0);
            $table->decimal('original_price', 15, 0)->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->json('specs')->nullable(); // {"screen":"...", "cpu":"...", "ram":"...", "storage":"...", "battery":"..."}
            $table->decimal('rating', 2, 1)->default(5.0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('status')->default('In Stock');
            $table->timestamps();
        });

        // Bảng đơn hàng
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id')->primary(); // ORD-XXXX
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name');
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->decimal('total', 15, 0);
            $table->string('status')->default('pending'); // pending, processing, completed, cancelled
            $table->string('payment_method')->default('cod'); // vnpay, cod
            $table->string('payment_status')->default('pending'); // pending, paid
            $table->timestamps();
        });

        // Bảng chi tiết đơn hàng
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // Lưu tên tại thời điểm đặt hàng
            $table->string('product_image')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('price', 15, 0); // Giá tại thời điểm đặt hàng
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
