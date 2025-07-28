# Password Reset API Documentation

This document provides a comprehensive guide to the password reset flow and API endpoints for the Spotlight application.

## Overview

The password reset process consists of three main steps:

1. **Request OTP**: User requests a one-time password (OTP) by providing their email
2. **Verify OTP**: User verifies the OTP received via email/SMS and receives a reset token
3. **Reset Password**: User submits a new password along with the reset token

## API Endpoints

### 1. Request Password Reset OTP

**Endpoint**: `POST /api/v1/auth/request-reset-otp`

**Request Body**:
```json
{
  "identifier": "user@example.com"  // Email
}
```

**Response (Success - 200 OK)**:
```json
{
  "success": true,
  "message": "If your account exists, an OTP has been sent to your email or phone"
}
```

**Notes**:
- The endpoint always returns a successful response regardless of whether the identifier exists in the system (to prevent user enumeration)
- If the identifier matches a valid user, a 6-digit OTP is sent via email (for email identifiers)
- Rate limiting is applied: maximum 5 requests per hour for the same identifier

### 2. Verify OTP

**Endpoint**: `POST /api/v1/auth/verify-reset-otp`

**Request Body**:
```json
{
  "identifier": "user@example.com",  // Email (same as previous step)
  "otp": "123456"                    // 6-digit OTP received via email
}
```

**Response (Success - 200 OK)**:
```json
{
  "success": true,
  "message": "OTP verified successfully",
  "resetToken": "a1b2c3d4e5f6g7h8i9j0..." // Token to use in the next step
}
```

**Response (Error - 400 Bad Request)**:
```json
{
  "success": false,
  "message": "Invalid OTP"  // Or "Invalid or expired OTP", "Too many failed attempts"
}
```

**Notes**:
- The OTP expires after 5 minutes
- Maximum 3 failed verification attempts are allowed before the OTP is invalidated
- Upon successful verification, a reset token is generated with a 15-minute expiration

### 3. Reset Password

**Endpoint**: `POST /api/v1/auth/reset-password`

**Request Body**:
```json
{
  "resetToken": "a1b2c3d4e5f6g7h8i9j0...",  // Token received from previous step
  "newPassword": "newSecurePassword",        // New password
  "newPassword_confirmation": "newSecurePassword"  // Confirmation of new password
}
```

**Response (Success - 200 OK)**:
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

**Response (Error - 400 Bad Request)**:
```json
{
  "success": false,
  "message": "Invalid or expired reset token"  // Or validation errors
}
```

**Notes**:
- The reset token expires after 15 minutes
- The token can only be used once
- Password must be at least 6 characters long
- Password confirmation is required

## Complete Flow Diagram

```
┌─────────────┐         ┌─────────────┐         ┌─────────────┐         ┌─────────────┐
│   User      │         │ Request OTP │         │  Verify OTP │         │    Reset    │
│  Interface  │         │   Endpoint  │         │   Endpoint  │         │  Password   │
└─────────────┘         └─────────────┘         └─────────────┘         └─────────────┘
       │                      │                       │                       │
       │  Request OTP         │                       │                       │
       │ ─────────────────────>                       │                       │
       │                      │                       │                       │
       │                      │  Send OTP via         │                       │
       │                      │  Email/SMS            │                       │
       │                      │ ─────────────────────>│                       │
       │                      │                       │                       │
       │  OTP Sent Response   │                       │                       │
       │ <─────────────────────                       │                       │
       │                      │                       │                       │
       │  Submit OTP          │                       │                       │
       │ ───────────────────────────────────────────────>                     │
       │                      │                       │                       │
       │                      │                       │  Generate Reset       │
       │                      │                       │  Token                │
       │                      │                       │ ─────────────────────>│
       │                      │                       │                       │
       │  Reset Token Response│                       │                       │
       │ <───────────────────────────────────────────────                     │
       │                      │                       │                       │
       │  Submit New Password │                       │                       │
       │  with Token          │                       │                       │
       │ ─────────────────────────────────────────────────────────────────────>
       │                      │                       │                       │
       │  Password Reset      │                       │                       │
       │  Confirmation        │                       │                       │
       │ <─────────────────────────────────────────────────────────────────────
       │                      │                       │                       │
```

## Security Features

1. **Privacy Protection**:
   - Response messages don't reveal if an account exists
   - Rate limiting is hidden from responses to prevent enumeration

2. **Rate Limiting**:
   - Maximum 5 OTP requests per hour per identifier
   - Prevents abuse of the system

3. **OTP Security**:
   - 6-digit numeric OTP (1,000,000 possible combinations)
   - Expires after 5 minutes
   - Maximum 3 verification attempts
   - Securely hashed in the database
   - Activity logged for security monitoring
   - Restricted to one successful use

4. **Token Security**:
   - 64-character random string (high entropy)
   - Single-use only
   - Expires after 15 minutes
   - Activity logged for security monitoring

## Implementation Notes

- OTP delivery is currently implemented for email
- SMS delivery is prepared in the code structure but requires an SMS provider integration
- All security events are logged for auditing purposes
- Passwords are securely hashed using Laravel's Hash facade (bcrypt)
