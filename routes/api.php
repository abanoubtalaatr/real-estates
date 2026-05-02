<?php

use App\Http\Controllers\Api\V1\Admin\AdminAgentController;
use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminPropertyController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\DeleteAccountController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\GoogleLoginController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\VerifyOtpController;
use App\Http\Controllers\Api\V1\Public\HomeController;
use App\Http\Controllers\Api\V1\Public\PropertyController;
use App\Http\Controllers\Api\V1\Public\PropertyReviewController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\User\ConversationController;
use App\Http\Controllers\Api\V1\User\FavoriteController;
use App\Http\Controllers\Api\V1\User\MessageController;
use App\Http\Controllers\Api\V1\User\OrderController;
use App\Http\Controllers\Api\V1\User\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('stripe/webhook', StripeWebhookController::class);

    Route::get('home', HomeController::class);

    Route::get('properties', [PropertyController::class, 'index']);
    Route::get('properties/{property}/similar', [PropertyController::class, 'similar'])->whereNumber('property');
    Route::get('properties/{property}', [PropertyController::class, 'show']);
    Route::get('properties/{property}/reviews', [PropertyReviewController::class, 'index'])->whereNumber('property');

    Route::post('auth/register', RegisterController::class);
    Route::post('auth/login', LoginController::class);
    Route::post('auth/google', GoogleLoginController::class);
    Route::post('auth/forgot-password', ForgotPasswordController::class);
    Route::post('auth/verify-otp', VerifyOtpController::class);
    Route::post('auth/reset-password', ResetPasswordController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', LogoutController::class);
        Route::get('auth/me', MeController::class);
        Route::put('auth/profile', [ProfileController::class, 'update']);
        Route::put('auth/password', ChangePasswordController::class);
        Route::delete('auth/account', DeleteAccountController::class);

        Route::get('me/recommendations', RecommendationController::class);

        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites', [FavoriteController::class, 'store']);
        Route::delete('favorites/{property}', [FavoriteController::class, 'destroy'])->whereNumber('property');

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);

        Route::post('properties/{property}/reviews', [PropertyReviewController::class, 'store'])->whereNumber('property');

        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);
    });

    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
        Route::get('dashboard', AdminDashboardController::class);

        Route::apiResource('categories', AdminCategoryController::class);
        Route::apiResource('properties', AdminPropertyController::class);
        Route::apiResource('agents', AdminAgentController::class);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::get('users/{user}', [AdminUserController::class, 'show']);
        Route::patch('users/{user}/block', [AdminUserController::class, 'updateBlock']);

        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
    });
});
