<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    // Mock OTP — replace with a real random generator + mail send in production
    private const MOCK_OTP = '123456';

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            DB::table('password_reset_otps')->updateOrInsert(
                ['email' => $request->email],
                ['otp' => self::MOCK_OTP, 'created_at' => now()],
            );

            // TODO: replace with a real Mailable when SMTP is configured
            Log::info("Password reset OTP for {$request->email}: " . self::MOCK_OTP);
        }

        return response()->json([
            'message' => 'If an account exists for that email, an OTP has been sent.',
        ]);
    }
}
