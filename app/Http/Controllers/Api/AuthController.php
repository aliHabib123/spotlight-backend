<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Apply JWT auth middleware to all methods except login and register
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Register a User.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        
        // Assign default role for mobile app users
        $user->assignRole('app user');
        
        // Generate token immediately after registration
        $token = auth('api')->login($user);
        
        // Return token along with user data
        return $this->respondWithToken($token, 'User successfully registered', 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = auth('api')->refresh();
            return $this->respondWithToken($newToken);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not refresh token'], 401);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get user roles and permissions if using Spatie permissions package
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [];
        $permissions = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [];
        
        return response()->json([
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     * @param  string|null $message
     * @param  int $statusCode
     *
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, string $message = null, int $statusCode = 200): JsonResponse
    {
        $user = auth('api')->user();
        
        // Get user roles and permissions if using Spatie permissions package
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [];
        $permissions = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [];
        
        // JWT TTL from config instead of using factory() method
        $expires_in = config('jwt.ttl', 60) * 60;
        
        $response = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expires_in,
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions
        ];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return response()->json($response, $statusCode);
    }
}
