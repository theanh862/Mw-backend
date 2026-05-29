<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Http;

class GoogleAuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Xác thực Google ID Token và tạo/cập nhật user.
     * Gửi token tới Google để xác minh, sau đó lấy thông tin user.
     *
     * @param string $idToken - Google credential token từ frontend
     * @return array{user: \App\Models\User, token: string}
     * @throws \Exception
     */
    public function authenticateWithGoogle(string $idToken): array
    {
        // Gọi Google API để xác thực ID Token
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            throw new \Exception('Google token không hợp lệ.');
        }

        $googleUser = $response->json();

        // Kiểm tra client_id có khớp không (bảo mật)
        $expectedClientId = config('services.google.client_id');
        if ($expectedClientId && $expectedClientId !== 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com') {
            if ($googleUser['aud'] !== $expectedClientId) {
                throw new \Exception('Google Client ID không khớp.');
            }
        }

        // Tạo hoặc cập nhật user
        $user = $this->userRepository->createOrUpdateFromGoogle([
            'google_id' => $googleUser['sub'],
            'name' => $googleUser['name'] ?? $googleUser['email'],
            'email' => $googleUser['email'],
            'avatar' => $googleUser['picture'] ?? null,
        ]);

        // Tạo Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
