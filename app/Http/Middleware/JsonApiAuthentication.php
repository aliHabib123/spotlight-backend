<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        
        try {
            if (!Auth::guard($guard)->check()) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required'
                ], 401);
            }

            // Add authenticated user to the request
            $request->setUserResolver(function () use ($guard) {
                return Auth::guard($guard)->user();
            });
            
            return $next($request);
        } catch (AuthenticationException $e) {
            return response()->json([
                'error' => 'Authentication Error',
                'message' => $e->getMessage() ?: 'Authentication failed'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server Error',
                'message' => 'An unexpected authentication error occurred'
            ], 500);
        }
    }
}
