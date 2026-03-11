<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Authentication
 *
 * Endpoints for obtaining and revoking API tokens.
 */
class TokenController extends Controller
{
    /**
     * Issue an API token
     *
     * Authenticate with email and password to receive a Sanctum plain-text token.
     * Include this token as a `Bearer` value in the `Authorization` header for all
     * subsequent authenticated requests.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "token": "1|laravel_sanctum_token_example",
     *   "user": {
     *     "id": "uuid-example",
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   }
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The given data was invalid.",
     *   "errors": { "email": ["The email field is required."] }
     * }
     * @response 401 scenario="Invalid credentials" {
     *   "message": "Invalid credentials."
     * }
     */
    public function store(LoginRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken($request->string('device_name', 'api'))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Revoke the current API token
     *
     * Invalidates the Bearer token used to make this request. After this call
     * the token can no longer be used to authenticate.
     *
     * @response 204 scenario="Token revoked" {}
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
