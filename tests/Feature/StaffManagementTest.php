<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $staff;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo các tài khoản test với vai trò khác nhau
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);

        $this->customer = User::create([
            'name' => 'Customer User',
            'email' => 'customer@test.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
    }

    /**
     * Admin có thể xem danh sách nhân viên.
     */
    public function test_admin_can_list_staff(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/admin/staff');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'email' => $this->staff->email,
                'role' => 'staff',
            ]);
    }

    /**
     * Admin có thể thêm mới nhân viên.
     */
    public function test_admin_can_create_staff(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/admin/staff', [
            'name' => 'New Staff Member',
            'email' => 'newstaff@test.com',
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'New Staff Member',
                'email' => 'newstaff@test.com',
                'role' => 'staff',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@test.com',
            'role' => 'staff',
        ]);
    }

    /**
     * Admin có thể cập nhật thông tin nhân viên.
     */
    public function test_admin_can_update_staff(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/admin/staff/{$this->staff->id}", [
            'name' => 'Updated Staff Name',
            'email' => 'updatedstaff@test.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Staff Name',
                'email' => 'updatedstaff@test.com',
                'role' => 'staff',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->staff->id,
            'name' => 'Updated Staff Name',
            'email' => 'updatedstaff@test.com',
        ]);
    }

    /**
     * Admin có thể xóa nhân viên.
     */
    public function test_admin_can_delete_staff(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->deleteJson("/api/admin/staff/{$this->staff->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Xóa nhân viên thành công.'
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $this->staff->id,
        ]);
    }

    /**
     * Staff không thể truy cập các tính năng chỉ dành cho Admin.
     */
    public function test_staff_cannot_access_admin_only_features(): void
    {
        Sanctum::actingAs($this->staff);

        // Không thể gọi api danh sách nhân viên
        $this->getJson('/api/admin/staff')->assertStatus(403);

        // Không thể tạo nhân viên mới
        $this->postJson('/api/admin/staff', [
            'name' => 'Another Staff',
            'email' => 'another@test.com',
            'password' => 'password',
        ])->assertStatus(403);

        // Không thể xem danh sách khách hàng
        $this->getJson('/api/admin/customers')->assertStatus(403);

        // Không thể xem dashboard stats
        $this->getJson('/api/admin/dashboard')->assertStatus(403);
    }

    /**
     * Customer không thể truy cập các tính năng quản lý nhân viên và sản phẩm.
     */
    public function test_customer_cannot_access_staff_and_admin_features(): void
    {
        Sanctum::actingAs($this->customer);

        $this->getJson('/api/admin/staff')->assertStatus(403);
        $this->postJson('/api/admin/categories', ['name' => 'New Cat'])->assertStatus(403);
    }

    /**
     * Staff có thể thực hiện quản lý danh mục và sản phẩm.
     */
    public function test_staff_can_manage_categories_and_products(): void
    {
        Sanctum::actingAs($this->staff);

        // Tạo danh mục mới
        $categoryResponse = $this->postJson('/api/admin/categories', [
            'name' => 'New Category',
        ]);
        $categoryResponse->assertStatus(201);
        $categoryId = $categoryResponse->json('id');

        // Tạo sản phẩm mới
        $productResponse = $this->postJson('/api/admin/products', [
            'category_id' => $categoryId,
            'name' => 'New Product',
            'price' => 100000,
        ]);
        $productResponse->assertStatus(201);
    }
}
