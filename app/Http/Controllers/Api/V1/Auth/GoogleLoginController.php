<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\GoogleLoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GoogleLoginController extends Controller
{
    /**
     * Flutter flow:
     *   1. User taps "Sign in with Google" in Flutter
     *   2. Flutter receives GoogleSignInAuthentication (via google_sign_in package)
     *   3. Flutter sends: POST /api/v1/auth/google  { "id_token": "<idToken>" }
     *   4. This controller verifies the token with Google and returns a Sanctum token
     */
    public function __invoke(GoogleLoginRequest $request): JsonResponse
    {
        $googleUser = $this->verifyGoogleToken($request->id_token);

        if (! $googleUser || ($googleUser['email_verified'] ?? '') !== 'true') {
            return response()->json([
                'message' => 'Invalid or expired Google token.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $this->findOrCreateUser($googleUser);

        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Account is blocked.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => [
                'user'       => new UserResource($user),
                'token'      => $user->createToken('google')->plainTextToken,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    private function findOrCreateUser(array $googleUser): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $googleUser['email']],
            [
                'name'      => $googleUser['name'] ?? $googleUser['email'],
                'google_id' => $googleUser['sub'],
                // Random secret: column is NOT NULL; User's "hashed" cast hashes once.
                // Email/password login stays disabled until the user sets a password (forgot-password flow).
                'password'  => Str::password(48),
                'role'      => UserRole::User,
            ]
        );

        if (! $user->google_id) {
            $user->update(['google_id' => $googleUser['sub']]);
        }

        return $user;
    }

    /**
     * Verifies the Google id_token and returns the payload or null on failure.
     *
     * Google's tokeninfo endpoint validates the token signature, expiry, and
     * issuer automatically. We additionally verify the audience (aud) matches
     * our app's client ID when GOOGLE_CLIENT_ID is set in .env.
     *
     * @return array<string, string>|null
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (empty($payload['sub']) || empty($payload['email'])) {
            return null;
        }

        // When GOOGLE_CLIENT_ID is configured, verify the token belongs to our app
        $clientId = config('services.google.client_id');
        if ($clientId && ($payload['aud'] ?? '') !== $clientId) {
            return null;
        }

        return $payload;
    }
}
