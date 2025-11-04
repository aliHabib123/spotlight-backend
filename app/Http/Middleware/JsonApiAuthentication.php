<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JsonApiAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $guard = null)
    {
        // Force the request to accept JSON responses
        $request->headers->set('Accept', 'application/json');
        
        // Get token for debugging
        $token = $request->bearerToken();
        $hasToken = !empty($token);
        
        try {
            // Try to authenticate with the specified guard
            if ($hasToken) {
                // Ensure the default guard for this request is the provided guard (e.g., 'api')
                if (!empty($guard)) {
                    Auth::shouldUse($guard);
                }
                // Explicit attempt to authenticate with token
                $authenticated = Auth::guard($guard)->check();
                
                if (!$authenticated) {
                    Log::info('Authentication failed with valid token format', [
                        'path' => $request->path(),
                        'guard' => $guard,
                        'token_present' => $hasToken
                    ]);
                    
                    return response()->json([
                        'error' => 'Unauthorized',
                        'message' => 'Authentication failed - Invalid or expired token',
                        'debug' => [
                            'token_exists' => $hasToken,
                            'guard' => $guard,
                            'path' => $request->path(),
                            'accept_header' => $request->header('Accept')
                        ]
                    ], 401);
                }
                
                // Add authenticated user to the request
                $request->setUserResolver(function () use ($guard) {
                    return Auth::guard($guard)->user();
                });
                
                return $next($request);
            } else {
                // No token provided
                Log::info('Authentication failed - No token provided', [
                    'path' => $request->path(),
                    'guard' => $guard
                ]);
                
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required - No token provided',
                    'debug' => [
                        'token_exists' => false,
                        'guard' => $guard,
                        'path' => $request->path(),
                        'headers' => $request->header()
                    ]
                ], 401);
            }
        } catch (AuthenticationException $e) {
            Log::error('Authentication exception', [
                'exception' => $e->getMessage(),
                'path' => $request->path()
            ]);
            
            return response()->json([
                'error' => 'Authentication Error',
                'message' => $e->getMessage() ?: 'Authentication failed',
                'debug' => [
                    'exception' => get_class($e),
                    'path' => $request->path(),
                    'guard' => $guard
                ]
            ], 401);
        } catch (\Exception $e) {
            Log::error('Unexpected authentication error', [
                'exception' => $e->getMessage(),
                'path' => $request->path()
            ]);
            
            return response()->json([
                'error' => 'Server Error',
                'message' => 'An unexpected authentication error occurred',
                'debug' => [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'path' => $request->path()
                ]
            ], 500);
        }
    }
}
