<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            // If the request wants JSON (API request) or URL starts with /api
            if ($request->expectsJson() || str_starts_with($request->path(), 'api')) {
                if ($e instanceof ValidationException) {
                    return new JsonResponse([
                        'error' => 'Validation failed',
                        'details' => $e->errors(),
                    ], 422);
                }

                if ($e instanceof AuthenticationException) {
                    return new JsonResponse([
                        'error' => 'Unauthorized',
                        'message' => 'Authentication required'
                    ], 401);
                }

                if ($e instanceof HttpException) {
                    return new JsonResponse([
                        'error' => 'Error',
                        'message' => $e->getMessage() ?: 'An error occurred'
                    ], $e->getStatusCode());
                }

                // Generic error response for any other exceptions in API requests
                $status = 500;
                $response = [
                    'error' => 'Server Error',
                    'message' => 'An unexpected error occurred'
                ];

                if (config('app.debug')) {
                    $response['debug'] = [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace()
                    ];
                }

                return new JsonResponse($response, $status);
            }
            
            // Let Laravel handle non-API exceptions normally
            return null;
        });
    }
}
