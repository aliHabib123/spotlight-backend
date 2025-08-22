<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Exception;

class SocialController extends Controller
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
        
        // Prepare user data with token information
        $userData = [];        
        if ($user) {
            $userData = [                
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'mobile' => $user->mobile,
                'mobile_country_code' => $user->mobile_country_code,
                'address' => $user->address,
                'provider_name' => $user->provider_name,
                'provider_id' => $user->provider_id,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ];            
        }
        
        // Get user roles and permissions if using Spatie permissions package
        if ($user) {
            try {
                if (method_exists($user, 'getRoleNames')) {
                    $userData['roles'] = $user->getRoleNames()->toArray();
                }
            } catch (\Exception $e) {
                $userData['roles'] = [];
            }
            
            try {
                if (method_exists($user, 'getAllPermissions')) {
                    $userData['permissions'] = $user->getAllPermissions()->pluck('name')->toArray();
                }
            } catch (\Exception $e) {
                $userData['permissions'] = [];
            }
            
            // Ensure email_verified flag is set
            $userData['email_verified'] = !is_null($user->email_verified_at);
        }
        
        $responseData = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl', 60) * 60,
            'user' => $userData
        ];
        
        return $this->successResponse($message, $responseData, $statusCode);
    }

    /**
     * Handle Google login
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        try {
            // Get user details from Google using the token provided by Flutter app
            $token = $request->token;
            
            try {
                // Use Google API Client to verify the ID token
                $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
                $payload = $client->verifyIdToken($token);
                
                if (!$payload) {
                    return $this->errorResponse('Invalid Google token', null, 401);
                }
                
                // Create a standardized user object with the token payload
                $googleUser = new class($payload) {
                    private $data;
                    
                    public function __construct($payload) {
                        $this->data = [
                            'email' => $payload['email'],
                            'name' => $payload['name'] ?? ($payload['given_name'] . ' ' . $payload['family_name']),
                            'nickname' => $payload['given_name'] ?? null,
                        ];
                    }
                    
                    public function getEmail() {
                        return $this->data['email'];
                    }
                    
                    public function getName() {
                        return $this->data['name'];
                    }
                    
                    public function getNickname() {
                        return $this->data['nickname'];
                    }
                };
            } catch (\Exception $e) {
                Log::error('Google OAuth error: ' . $e->getMessage());
                return $this->errorResponse('Invalid Google token or OAuth configuration error', null, 401);
            }

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'username' => $this->generateUsername($googleUser->getNickname() ?? $googleUser->getName()),
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(), // Google emails are already verified
                ]
            );

            // Assign app user role if new user
            if ($user->wasRecentlyCreated) {
                $user->assignRole('app user');
            }

            // Generate JWT token
            $jwtToken = JWTAuth::fromUser($user);

            return $this->respondWithToken($jwtToken, 'Successfully logged in with Google', 200);
        } catch (Exception $e) {
            Log::error('Google login failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->errorResponse('Google login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Handle Facebook login
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function facebookLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors(), 422);
        }

        try {
            // Get user details from Facebook using the token provided by Flutter app
            $token = $request->token;
            
            try {
                // Use Facebook PHP SDK to get user information from the access token
                $fb = new \Facebook\Facebook([
                    'app_id' => config('services.facebook.client_id'),
                    'app_secret' => config('services.facebook.client_secret'),
                    'default_graph_version' => 'v18.0',
                ]);
                
                try {
                    $response = $fb->get('/me?fields=id,name,email', $token);
                    $fbUserData = $response->getGraphUser();
                    
                    // Create a standardized user object
                    $fbUser = new class($fbUserData) {
                        private $data;
                        
                        public function __construct($fbUserData) {
                            $this->data = [
                                'email' => $fbUserData->getEmail(),
                                'name' => $fbUserData->getName(),
                            ];
                        }
                        
                        public function getEmail() {
                            return $this->data['email'];
                        }
                        
                        public function getName() {
                            return $this->data['name'];
                        }
                    };
                } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                    Log::error('Facebook Graph error: ' . $e->getMessage());
                    return $this->errorResponse('Invalid Facebook token', null, 401);
                } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                    Log::error('Facebook SDK error: ' . $e->getMessage());
                    return $this->errorResponse('Facebook SDK error', null, 500);
                }
            } catch (\Exception $e) {
                Log::error('Facebook OAuth error: ' . $e->getMessage());
                return $this->errorResponse('Invalid Facebook token or OAuth configuration error', null, 401);
            }

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $fbUser->getEmail()],
                [
                    'name' => $fbUser->getName(),
                    'username' => $this->generateUsername($fbUser->getName()),
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(), // Facebook emails are considered verified
                ]
            );

            // Assign app user role if new user
            if ($user->wasRecentlyCreated) {
                $user->assignRole('app user');
            }

            // Generate JWT token
            $jwtToken = JWTAuth::fromUser($user);

            return $this->respondWithToken($jwtToken, 'Successfully logged in with Facebook', 200);
        } catch (Exception $e) {
            Log::error('Facebook login failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return $this->errorResponse('Facebook login failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Generate a unique username based on the provided name
     * 
     * @param string $name
     * @return string
     */
    protected function generateUsername(string $name): string
    {
        // Convert name to lowercase and replace spaces with underscores
        $baseUsername = strtolower(str_replace(' ', '_', $name));
        
        // Remove special characters
        $baseUsername = preg_replace('/[^A-Za-z0-9_]/', '', $baseUsername);
        
        // Check if username exists
        $username = $baseUsername;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
}
