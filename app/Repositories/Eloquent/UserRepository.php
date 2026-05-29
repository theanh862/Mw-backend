<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function getAll()
    {
        return User::withCount('orders')->orderBy('created_at', 'desc')->get();
    }

    public function findById(int $id)
    {
        return User::findOrFail($id);
    }

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function findByGoogleId(string $googleId)
    {
        return User::where('google_id', $googleId)->first();
    }

    /**
     * Tạo mới hoặc cập nhật user từ thông tin Google.
     * Nếu đã tồn tại user với google_id thì cập nhật name/avatar.
     * Nếu chưa có thì tạo mới với role = 'customer'.
     */
    public function createOrUpdateFromGoogle(array $data)
    {
        return User::updateOrCreate(
            ['google_id' => $data['google_id']],
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'avatar' => $data['avatar'] ?? null,
                'password' => bcrypt(Str::random(24)), // mật khẩu ngẫu nhiên vì dùng SSO
            ]
        );
    }

    public function count()
    {
        return User::count();
    }
}
