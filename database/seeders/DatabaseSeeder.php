<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Tạo Users
        $admin = User::create([
            'name' => 'MW Admin',
            'email' => 'admin@mobileworld.com',
            'password' => Hash::make('adminpassword'),
            'role' => 'admin',
        ]);

        $customer1 = User::create([
            'name' => 'Nguyễn Văn A',
            'email' => 'nva@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $customer2 = User::create([
            'name' => 'Trần Thị B',
            'email' => 'ttb@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // 2. Tạo Categories
        $categoriesData = [
            ['id' => 1, 'name' => 'Điện thoại', 'slug' => 'phone', 'icon' => 'Smartphone'],
            ['id' => 2, 'name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'Laptop'],
            ['id' => 3, 'name' => 'Tablet', 'slug' => 'tablet', 'icon' => 'Tablet'],
            ['id' => 4, 'name' => 'Phụ kiện', 'slug' => 'accessories', 'icon' => 'Watch'],
        ];

        foreach ($categoriesData as $cat) {
            Category::create($cat);
        }

        // 3. Tạo Products
        $productsData = [
            [
                'id' => 1,
                'category_id' => 1,
                'name' => 'iPhone 15 Pro Max 256GB',
                'price' => 34990000,
                'original_price' => 38990000,
                'image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&q=80&w=600',
                'description' => 'iPhone 15 Pro Max là dòng iPhone cao cấp nhất với chất liệu Titanium hàng không vũ trụ siêu bền, camera zoom 5x quang học đỉnh cao, hiệu năng quái vật từ chip A17 Pro.',
                'specs' => [
                    'screen' => '6.7 inches, Super Retina XDR OLED, 120Hz',
                    'cpu' => 'Apple A17 Pro 6 nhân',
                    'ram' => '8 GB',
                    'storage' => '256 GB',
                    'battery' => '4441 mAh, Sạc nhanh 20W'
                ],
                'rating' => 4.8,
                'reviews_count' => 124,
                'status' => 'In Stock'
            ],
            [
                'id' => 2,
                'category_id' => 2,
                'name' => 'Macbook Air M3 8GB/256GB',
                'price' => 27990000,
                'original_price' => 29990000,
                'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&q=80&w=600',
                'description' => 'Macbook Air M3 2024 mang đến sự kết hợp hoàn hảo giữa độ mỏng nhẹ tối đa và sức mạnh xử lý vượt trội nhờ chip Apple M3 tiên tiến, thời lượng pin đến 18 tiếng liên tục.',
                'specs' => [
                    'screen' => '13.6 inches Liquid Retina, 2560x1664 pixels',
                    'cpu' => 'Apple M3 8-core CPU',
                    'ram' => '8 GB',
                    'storage' => '256 GB SSD',
                    'battery' => 'Thời lượng pin lên tới 18 giờ'
                ],
                'rating' => 4.9,
                'reviews_count' => 88,
                'status' => 'In Stock'
            ],
            [
                'id' => 3,
                'category_id' => 1,
                'name' => 'Samsung Galaxy S24 Ultra 5G',
                'price' => 29990000,
                'original_price' => 33990000,
                'image' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&q=80&w=600',
                'description' => 'Samsung Galaxy S24 Ultra định nghĩa lại trải nghiệm smartphone với quyền năng Galaxy AI vượt trội, camera 200MP zoom không gian 100x và bút S-Pen đa năng tiện lợi.',
                'specs' => [
                    'screen' => '6.8 inches, Dynamic AMOLED 2X, QHD+, 120Hz',
                    'cpu' => 'Snapdragon 8 Gen 3 for Galaxy',
                    'ram' => '12 GB',
                    'storage' => '256 GB',
                    'battery' => '5000 mAh, Sạc nhanh 45W'
                ],
                'rating' => 4.7,
                'reviews_count' => 145,
                'status' => 'In Stock'
            ],
            [
                'id' => 4,
                'category_id' => 3,
                'name' => 'iPad Pro M4 11 inch 256GB Wifi',
                'price' => 28990000,
                'original_price' => 29990000,
                'image' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&q=80&w=600',
                'description' => 'iPad Pro M4 siêu mỏng, hiệu năng vượt bậc với vi xử lý Apple M4 thế hệ mới, cùng màn hình Ultra Retina XDR sử dụng công nghệ OLED hai lớp tiên tiến nhất thế giới.',
                'specs' => [
                    'screen' => '11 inches Ultra Retina XDR OLED, 120Hz',
                    'cpu' => 'Apple M4 9-core CPU',
                    'ram' => '8 GB',
                    'storage' => '256 GB',
                    'battery' => 'Pin sạc Li-Po công suất 31.29 Wh'
                ],
                'rating' => 4.9,
                'reviews_count' => 62,
                'status' => 'In Stock'
            ],
            [
                'id' => 5,
                'category_id' => 4,
                'name' => 'Apple Watch Series 9 GPS 41mm',
                'price' => 9490000,
                'original_price' => 10490000,
                'image' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&q=80&w=600',
                'description' => 'Apple Watch Series 9 sở hữu màn hình sáng vượt trội, tính năng Double Tap chạm hai lần để tương tác cực kỳ độc đáo và các cảm biến sức khoẻ tân tiến hàng đầu.',
                'specs' => [
                    'screen' => 'OLED Retina luôn bật, 2000 nits',
                    'cpu' => 'Apple S9 SiP',
                    'ram' => 'Không công bố',
                    'storage' => '64 GB',
                    'battery' => 'Lên đến 18 giờ (36 giờ chế độ tiết kiệm)'
                ],
                'rating' => 4.6,
                'reviews_count' => 95,
                'status' => 'In Stock'
            ],
            [
                'id' => 6,
                'category_id' => 4,
                'name' => 'Tai nghe Bluetooth Apple AirPods Pro 2 USB-C',
                'price' => 5790000,
                'original_price' => 6190000,
                'image' => 'https://images.unsplash.com/photo-1588449668365-d15e397f6787?auto=format&fit=crop&q=80&w=600',
                'description' => 'AirPods Pro thế hệ thứ 2 mang lại khả năng chống ồn chủ động (ANC) tốt gấp hai lần phiên bản tiền nhiệm, cổng sạc Type-C hiện đại và chất âm vòm 3D hoàn mỹ.',
                'specs' => [
                    'screen' => 'Không có',
                    'cpu' => 'Apple H2 chip',
                    'ram' => 'Không có',
                    'storage' => 'Không có',
                    'battery' => 'Lên đến 6 giờ nghe (30 giờ kèm hộp sạc)'
                ],
                'rating' => 4.8,
                'reviews_count' => 210,
                'status' => 'In Stock'
            ]
        ];

        foreach ($productsData as $prod) {
            $product = Product::create($prod);

            if ($product->id === 1) {
                \App\Models\ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => 'IP15PM-256-NAT',
                    'name' => 'Titanium Tự Nhiên',
                    'price' => 34990000,
                    'original_price' => 38990000,
                    'stock' => 15,
                    'image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&q=80&w=600',
                ]);
                \App\Models\ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => 'IP15PM-256-BLK',
                    'name' => 'Titanium Đen',
                    'price' => 34500000,
                    'original_price' => 38990000,
                    'stock' => 20,
                    'image' => 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&q=80&w=600',
                ]);
                \App\Models\ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => 'IP15PM-256-BLU',
                    'name' => 'Titanium Xanh',
                    'price' => 33990000,
                    'original_price' => 38990000,
                    'stock' => 5,
                    'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&q=80&w=600',
                ]);
            }

            if ($product->id === 3) {
                \App\Models\ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => 'S24U-256-GRY',
                    'name' => 'Titanium Xám',
                    'price' => 29990000,
                    'original_price' => 33990000,
                    'stock' => 12,
                    'image' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&q=80&w=600',
                ]);
                \App\Models\ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => 'S24U-256-YLW',
                    'name' => 'Titanium Vàng',
                    'price' => 29500000,
                    'original_price' => 33990000,
                    'stock' => 8,
                    'image' => 'https://images.unsplash.com/photo-1573148195900-7845dcb9c127?auto=format&fit=crop&q=80&w=600',
                ]);
                \App\Models\ProductSku::create([
                    'product_id' => $product->id,
                    'sku' => 'S24U-256-BLK',
                    'name' => 'Titanium Đen',
                    'price' => 28990000,
                    'original_price' => 33990000,
                    'stock' => 3,
                    'image' => 'https://images.unsplash.com/photo-1565630916779-e303be97b6f5?auto=format&fit=crop&q=80&w=600',
                ]);
            }
        }

        // 4. Tạo Orders & OrderItems
        // Đơn 1
        $order1 = Order::create([
            'id' => 'ORD-1001',
            'user_id' => $customer1->id,
            'customer_name' => 'Nguyễn Văn A',
            'email' => 'nva@gmail.com',
            'phone' => '0987654321',
            'address' => '123 Đường Nguyễn Huệ, Quận 1, TP. HCM',
            'total' => 34990000,
            'status' => 'pending',
            'payment_method' => 'vnpay',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => 1,
            'product_name' => 'iPhone 15 Pro Max 256GB',
            'product_image' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&q=80&w=600',
            'quantity' => 1,
            'price' => 34990000,
        ]);

        // Đơn 2
        $order2 = Order::create([
            'id' => 'ORD-1002',
            'user_id' => $customer2->id,
            'customer_name' => 'Trần Thị B',
            'email' => 'ttb@gmail.com',
            'phone' => '0912345678',
            'address' => '456 Đường Lê Lợi, Hải Châu, Đà Nẵng',
            'total' => 37480000,
            'status' => 'processing',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => 5,
            'product_name' => 'Apple Watch Series 9 GPS 41mm',
            'product_image' => 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?auto=format&fit=crop&q=80&w=600',
            'quantity' => 1,
            'price' => 9490000,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => 2,
            'product_name' => 'Macbook Air M3 8GB/256GB',
            'product_image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&q=80&w=600',
            'quantity' => 1,
            'price' => 27990000,
        ]);

        // 5. Tạo Reviews
        \App\Models\ProductReview::create([
            'product_id' => 1,
            'user_id' => $customer1->id,
            'user_name' => 'Nguyễn Văn A',
            'rating' => 5,
            'content' => 'Điện thoại dùng rất mượt mà, chụp ảnh zoom 5x siêu nét. Rất đáng tiền!',
        ]);

        \App\Models\ProductReview::create([
            'product_id' => 1,
            'user_id' => $customer2->id,
            'user_name' => 'Trần Thị B',
            'rating' => 4,
            'content' => 'Máy đẹp, cầm nhẹ tay hơn bản 14 Pro Max. Pin trâu dùng cả ngày không lo hết.',
        ]);

        \App\Models\ProductReview::create([
            'product_id' => 3,
            'user_id' => $customer1->id,
            'user_name' => 'Nguyễn Văn A',
            'rating' => 5,
            'content' => 'Bút S-Pen quá tiện lợi cho công việc văn phòng, Galaxy AI dịch thuật rất chuẩn.',
        ]);

        foreach ([1, 3] as $pId) {
            $p = Product::find($pId);
            if ($p) {
                $p->update([
                    'rating' => $p->reviews()->avg('rating'),
                    'reviews_count' => $p->reviews()->count(),
                ]);
            }
        }
    }
}

