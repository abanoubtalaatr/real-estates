<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerifyOtpController extends Controller
{
    public function __invoke(VerifyOtpRequest $request): JsonResponse
    {
        $record = DB::table('password_reset_otps')
            ->where('email', $request->email)
            ->first();

        if (! $record || $record->otp !== $request->otp) {
            return response()->json([
                'message' => 'The OTP is invalid or has expired.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => 'OTP verified successfully. You may now reset your password.',
        ]);
    }
}
