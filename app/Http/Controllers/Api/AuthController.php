<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Return a standardized API response
     * 
     * @param string $status Status of the response (success|error)
     * @param string|null $message Optional message
     * @param array $data Additional data to include in response
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    protected function apiResponse(string $status, ?string $message = null, array $data = [], int $statusCode = 200): JsonResponse
    {
        $response = ['status' => $status];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Return a standardized success response
     * 
     * @param string|null $message Success message
     * @param array $data Additional data to include in response
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    protected function successResponse(?string $message = null, array $data = [], int $statusCode = 200): JsonResponse
    {
        return $this->apiResponse('success', $message, $data, $statusCode);
    }
    
    /**
     * Return a standardized error response
     * 
     * @param string $message Error message
     * @param mixed|null $errors Validation errors if any
     * @param int $statusCode HTTP status code
     * @return JsonResponse
     */
    protected function errorResponse(string $message, $errors = null, int $statusCode = 400): JsonResponse
    {
        $data = [];
        
        if ($errors) {
            $data['errors'] = $errors;
        }
        
        return $this->apiResponse('error', $message, $data, $statusCode);
    }
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
     * Get a JWT via given credentials (email or username).
     *
     * @param  Request  $request
     * @return JsonResponse
     * 
     * @response 200 {
     *   "status": "success",
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     *   "token_type": "bearer",
     *   "expires_in": 3600,
     *   "user": {...}
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Unauthorized"
     * }
     * 
     * @response 422 {
     *   "status": "error",
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": ["The email field is required."],
     *     "password": ["The password field is required."]
     *   }
     * }
     */
    public function login(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }
        
        // Determine if login is email or username
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // Authenticate with determined login type
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password
        ];
        
        // Attempt authentication
        $token = auth('api')->attempt($credentials);
        
        if (!$token) {
            return $this->errorResponse('Invalid credentials', null, 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Register a User.
     *
     * @param  Request  $request
     * @return JsonResponse
     * 
     * @response 201 {
     *   "status": "success",
     *   "message": "User successfully registered",
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     *   "token_type": "bearer",
     *   "expires_in": 3600,
     *   "user": {...}
     * }
     * 
     * @response 400 {
     *   "status": "error",
     *   "message": "Validation failed",
     *   "errors": {
     *     "username": ["The username has already been taken."],
     *     "email": ["The email has already been taken."],
     *     "password": ["The password confirmation does not match."]
     *   }
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'username' => 'required|string|between:3,50|unique:users',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'mobile' => $request->mobile,
            'address' => $request->address,
        ]);
        
        // Assign default role for mobile app users
        $user->assignRole('app user');
        
        // Login and get token
        auth('api')->login($user);
        $token = JWTAuth::fromUser($user);
        
        // Return token along with user data
        return $this->respondWithToken($token, 'User successfully registered', 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return $this->successResponse('Successfully logged out');
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     * 
     * @response 200 {
     *   "status": "success",
     *   "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     *   "token_type": "bearer",
     *   "expires_in": 3600,
     *   "user": {...}
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Could not refresh token: Token has expired"
     * }
     */
    public function refresh(): JsonResponse
    {
        try {
            // Get the token provider and refresh the token
            // Using JWTAuth facade for token refresh
            // This requires tymon/jwt-auth and should work if properly configured
            $token = JWTAuth::getToken();
            $newToken = JWTAuth::refresh($token);
            return $this->respondWithToken($newToken);
        } catch (\Exception $e) {
            return $this->errorResponse('Could not refresh token: ' . $e->getMessage(), null, 401);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     * 
     * @response 200 {
     *   "status": "success",
     *   "user": {...},
     *   "roles": [...],
     *   "permissions": [...]
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Unauthorized"
     * }
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        
        if (!$user) {
            return $this->errorResponse('Unauthorized', null, 401);
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
     * @param string|null $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $user = auth('api')->user();
        
        // Get user roles and permissions if using Spatie permissions package
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [];
        $permissions = method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->pluck('name') : [];
        
        // JWT TTL from config instead of using factory() method
        // Prepare user data with token information
        $userData = $user ? $user->toArray() : [];
        $userData['roles'] = $roles;
        $userData['permissions'] = $permissions;
        
        $responseData = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 60) * 60,
            'user' => $userData
        ];
        
        return $this->successResponse($message, $responseData, $statusCode);
    }
    
    /**
     * Update the authenticated user's profile.
     *
     * @param  Request  $request
     * @return JsonResponse
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Profile updated successfully",
     *   "user": {...},
     *   "roles": [...],
     *   "permissions": [...]
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Unauthorized"
     * }
     * 
     * @response 422 {
     *   "status": "error",
     *   "message": "Validation failed",
     *   "errors": {
     *     "name": ["The name field is required."],
     *     "email": ["This email is already in use."]
     *   }
     * }
     * 
     * @response 500 {
     *   "status": "error",
     *   "message": "Failed to update profile: Database connection error"
     * }
     */
    public function updateProfile(Request $request): JsonResponse
    {
        // Get the authenticated user
        $user = auth('api')->user();
        
        if (!$user) {
            return $this->errorResponse('Unauthorized', null, 401);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users,email,'.$user->id,
            'password' => 'nullable|string|confirmed|min:6',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }
        
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address
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
            
            return $this->successResponse('Profile updated successfully', [
                'user' => $updatedUser,
                'roles' => $roles,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Delete the authenticated user's account.
     *
     * @param  Request  $request
     * @return JsonResponse
     * 
     * @response 200 {
     *   "status": "success",
     *   "message": "Account successfully deleted"
     * }
     * 
     * @response 401 {
     *   "status": "error",
     *   "message": "Unauthorized"
     * }
     * 
     * @response 422 {
     *   "status": "error",
     *   "message": "Validation failed",
     *   "errors": {
     *     "password": ["The provided password is incorrect."]
     *   }
     * }
     * 
     * @response 500 {
     *   "status": "error",
     *   "message": "Failed to delete account: Database error"
     * }
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Store user ID before we do anything else
            $userId = $user->id;
            
            // Verify password to confirm deletion request
            $validator = Validator::make($request->all(), [
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Check if the provided password matches
            if (!Hash::check((string)$request->password, (string)$user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect'
                ], 422);
            }
            
            // Delete the user account first
            $deleted = User::where('id', $userId)->delete();
            
            if (!$deleted) {
                return $this->errorResponse('Failed to delete account', null, 500);
            }
            
            // Log the user out by invalidating their token
            auth('api')->logout();
            
            return $this->successResponse('Account successfully deleted');
        } catch (\Exception $e) {
            // Log the exception but ensure we return JSON
            Log::error('Account deletion failed: ' . $e->getMessage(), [
                'user_id' => $request->user('api') ? $request->user('api')->id : null,
                'exception' => $e
            ]);
            
            return $this->errorResponse('Failed to delete account: An unexpected error occurred', null, 500);
        }
    }
}
