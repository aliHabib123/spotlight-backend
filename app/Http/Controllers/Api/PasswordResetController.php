<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtp as PasswordResetOtpNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Request a password reset OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestResetOtp(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string', // Email or phone
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $identifier = $request->identifier;
        
        // Log the request (without revealing if user exists)
        Log::info('Password reset OTP requested', [
            'identifier_hash' => hash('sha256', $identifier), // Hash for privacy
            'ip' => $request->ip(),
        ]);

        // Check if user exists (but don't reveal this in the response)
        $user = User::where('email', $identifier)
            ->orWhere('mobile', $identifier)
            ->first();

        if ($user) {
            // Check for rate limiting - max 5 requests per hour for same identifier
            $hourAgo = Carbon::now()->subHour();
            $recentRequests = PasswordResetOtp::where('identifier', $identifier)
                ->where('created_at', '>=', $hourAgo)
                ->count();

            if ($recentRequests >= 5) {
                Log::warning('Password reset rate limit exceeded', [
                    'identifier_hash' => hash('sha256', $identifier),
                    'ip' => $request->ip(),
                ]);
                
                // Don't reveal rate limiting to prevent user enumeration
                return response()->json([
                    'success' => true,
                    'message' => 'If your account exists, an OTP has been sent to your email or phone'
                ]);
            }

            // Generate a 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = Hash::make($otp);
            
            // Store the OTP with expiration (5 minutes)
            PasswordResetOtp::create([
                'identifier' => $identifier,
                'otp_hash' => $otpHash,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);

            // Log the OTP for debugging (should be removed in production)
            Log::info('OTP generated for testing', [
                'identifier_hash' => hash('sha256', $identifier),
                'otp' => $otp, // REMOVE THIS IN PRODUCTION
            ]);
            
            // Send the OTP via email
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                // If identifier is an email, send email notification
                $user->notify(new PasswordResetOtpNotification($otp));
                Log::info('OTP email notification sent', [
                    'identifier_hash' => hash('sha256', $identifier),
                ]);
            } else {
                // If identifier is a phone number, we would implement SMS sending here
                // For now, just log that we would send SMS
                Log::info('SMS would be sent (not implemented)', [
                    'identifier_hash' => hash('sha256', $identifier),
                ]);
                // TODO: Implement SMS sending
            }
        }

        // Always return the same response whether user exists or not
        return response()->json([
            'success' => true,
            'message' => 'If your account exists, an OTP has been sent to your email or phone'
        ]);
    }

    /**
     * Verify the password reset OTP
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyResetOtp(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $identifier = $request->identifier;
        $otp = $request->otp;

        // Log the verification attempt (without revealing if user exists)
        Log::info('Password reset OTP verification attempt', [
            'identifier_hash' => hash('sha256', $identifier),
            'ip' => $request->ip(),
        ]);

        // Find the most recent unexpired and unused OTP for this identifier
        $otpRecord = PasswordResetOtp::where('identifier', $identifier)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Check if max attempts exceeded
        if ($otpRecord->hasExceededMaxAttempts()) {
            $otpRecord->markAsUsed(); // Invalidate the OTP
            
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.'
            ], 400);
        }

        // Verify the OTP
        if (!$otpRecord->verifyOtp($otp)) {
            // Increment attempt counter
            $otpRecord->incrementAttemptCount();
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP'
            ], 400);
        }

        // OTP is valid, generate a reset token
        $resetToken = $otpRecord->generateResetToken();
        
        // Set expiration for the reset token (15 minutes)
        $otpRecord->expires_at = Carbon::now()->addMinutes(15);
        $otpRecord->save();

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'resetToken' => $resetToken
        ]);
    }

    /**
     * Reset the password using the token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'resetToken' => 'required|string',
            'newPassword' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        // Find the token record
        $tokenRecord = PasswordResetOtp::where('reset_token', $request->resetToken)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$tokenRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ], 400);
        }

        // Find the user
        $user = User::where('email', $tokenRecord->identifier)
            ->orWhere('mobile', $tokenRecord->identifier)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Update the password
        $user->password = Hash::make($request->newPassword);
        $user->save();

        // Mark the token as used
        $tokenRecord->markAsUsed();

        // Log the password reset (without sensitive info)
        Log::info('Password reset successful', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }
}
