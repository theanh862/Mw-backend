<?php

namespace App\Http\Controllers;

use App\Services\GoogleAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $googleAuthService;

    public function __construct(GoogleAuthService $googleAuthService)
    {
        $this->googleAuthService = $googleAuthService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng.'], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    /**
     * Authenticate with Google ID Token.
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'credential' => 'required|string',
        ]);

        try {
            $token = $request->input('credential');
            $authData = $this->googleAuthService->authenticateWithGoogle($token);

            if (!$authData) {
                return response()->json([
                    'message' => 'Xác thực Google thất bại.',
                ], 401);
            }

            return response()->json([
                'token' => $authData['token'],
                'user' => $authData['user'],
            ]);
        } catch (\Exception $e) {
            Log::error('Google login error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Đã có lỗi xảy ra trong quá trình xác thực.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'phone'   => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
        ]);

        $request->user()->update($data);

        return response()->json($request->user()->fresh());
    }

    /**
     * Log the user out (revoke token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công.',
        ]);
    }
}
