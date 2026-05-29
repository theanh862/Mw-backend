<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function getAll()
    {
        return Order::with('items')->orderBy('created_at', 'desc')->get();
    }

    public function getByUserId(int $userId)
    {
        return Order::with('items')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(string $id)
    {
        return Order::with('items')->findOrFail($id);
    }

    public function create(array $data)
    {
        return Order::create($data);
    }

    public function update(string $id, array $data)
    {
        $order = Order::findOrFail($id);
        $order->update($data);
        return $order->fresh('items');
    }

    public function delete(string $id)
    {
        return Order::findOrFail($id)->delete();
    }

    public function count()
    {
        return Order::count();
    }

    public function totalRevenue()
    {
        return Order::where('payment_status', 'paid')->sum('total');
    }
}
