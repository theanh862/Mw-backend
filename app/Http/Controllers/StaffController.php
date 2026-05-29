<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * Lấy danh sách nhân viên (Staff).
     */
    public function index()
    {
        $staffList = User::where('role', 'staff')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'createdAt' => $user->created_at->toIso8601String(),
                ];
            });

        return response()->json($staffList);
    }

    /**
     * Thêm mới nhân viên vào hệ thống.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $staff = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'staff',
        ]);

        return response()->json([
            'message' => 'Thêm nhân viên thành công.',
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'avatar' => $staff->avatar,
                'role' => $staff->role,
                'createdAt' => $staff->created_at->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * Cập nhật thông tin nhân viên.
     */
    public function update(Request $request, $id)
    {
        $staff = User::where('id', $id)->where('role', 'staff')->first();

        if (!$staff) {
            return response()->json([
                'message' => 'Không tìm thấy nhân viên.'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
        ]);

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (!empty($updateData)) {
            $staff->update($updateData);
        }

        return response()->json([
            'message' => 'Cập nhật nhân viên thành công.',
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'avatar' => $staff->avatar,
                'role' => $staff->role,
                'createdAt' => $staff->created_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Xóa nhân viên khỏi hệ thống.
     */
    public function destroy($id)
    {
        $staff = User::where('id', $id)->where('role', 'staff')->first();

        if (!$staff) {
            return response()->json([
                'message' => 'Không tìm thấy nhân viên.'
            ], 404);
        }

        $staff->delete();

        return response()->json([
            'message' => 'Xóa nhân viên thành công.'
        ]);
    }
}
