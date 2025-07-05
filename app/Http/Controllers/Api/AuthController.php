<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        $token = auth('api')->attempt($credentials);
        if (!$token) {
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
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mobile' => $request->mobile,
            'address' => $request->address,
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
    protected function respondWithToken(string $token, string $message = '', int $statusCode = 200): JsonResponse
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
        
        if ($message !== '') {
            $response['message'] = $message;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Update the authenticated user's profile.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        // Get the authenticated user
        $user = auth('api')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users,email,'.$user->id,
            'password' => 'nullable|string|confirmed|min:6',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address,
        ];
        
        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }
        
        try {
            User::where('id', $user->id)->update($updateData);
            
            // Get the refreshed user data
            $updatedUser = User::find($user->id);
            
            // Get user roles and permissions
            $roles = method_exists($updatedUser, 'getRoleNames') ? $updatedUser->getRoleNames() : [];
            $permissions = method_exists($updatedUser, 'getAllPermissions') ? $updatedUser->getAllPermissions()->pluck('name') : [];
            
            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $updatedUser,
                'roles' => $roles,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update profile', 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete the authenticated user's account.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // Store user ID before we do anything else
            $userId = $user->id;
            
            // Verify password to confirm deletion request
            $validator = Validator::make($request->all(), [
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
            }
            
            // Check if the provided password matches
            if (!Hash::check((string)$request->password, (string)$user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 422);
            }
            
            // Delete the user account first
            $deleted = User::where('id', $userId)->delete();
            
            if (!$deleted) {
                return response()->json(['error' => 'Failed to delete account'], 500);
            }
            
            // Log the user out by invalidating their token
            auth('api')->logout();
            
            return response()->json(['message' => 'Account successfully deleted'], 200);
        } catch (\Exception $e) {
            // Log the exception but ensure we return JSON
            Log::error('Account deletion failed: ' . $e->getMessage(), [
                'user_id' => $request->user('api') ? $request->user('api')->id : null,
                'exception' => $e
            ]);
            
            return response()->json([
                'error' => 'Failed to delete account',
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }
}
