<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetOtp extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'identifier',
        'otp_hash',
        'reset_token',
        'expires_at',
        'is_used',
        'attempt_count',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];
    
    /**
     * Check if the OTP has exceeded maximum attempts (3 attempts)
     *
     * @return bool
     */
    public function hasExceededMaxAttempts(): bool
    {
        return $this->attempt_count >= 3;
    }
    
    /**
     * Increment the attempt count
     *
     * @return void
     */
    public function incrementAttemptCount(): void
    {
        $this->attempt_count += 1;
        $this->save();
    }
    
    /**
     * Verify the provided OTP
     *
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(string $otp): bool
    {
        return Hash::check($otp, $this->otp_hash);
    }
    
    /**
     * Mark this OTP record as used
     *
     * @return void
     */
    public function markAsUsed(): void
    {
        $this->is_used = true;
        $this->save();
    }
    
    /**
     * Generate a reset token for this OTP
     *
     * @return string
     */
    public function generateResetToken(): string
    {
        $resetToken = Str::random(64);
        $this->reset_token = $resetToken;
        $this->save();
        
        return $resetToken;
    }
}
