# Social Login API Documentation

This document provides guidelines for implementing social login with Google and Facebook in a Flutter application, using our Laravel backend API.

## API Endpoints

### Google Login
```
POST /api/v1/auth/google
```

**Request:**
```json
{
  "token": "Google ID token obtained from Google Sign-In"
}
```

### Facebook Login
```
POST /api/v1/auth/facebook
```

**Request:**
```json
{
  "token": "Facebook access token obtained from Facebook Login"
}
```

**Response (Success - for both endpoints):**
```json
{
  "status": "success",
  "message": "Successfully logged in with Google|Facebook",
  "access_token": "JWT_TOKEN_HERE",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "username": "username",
    "mobile": null,
    "mobile_country_code": null,
    "address": null,
    "provider_name": "google|facebook",
    "provider_id": "provider-specific-id",
    "email_verified_at": "2025-08-21T00:00:00.000000Z",
    "created_at": "2025-08-21T00:00:00.000000Z",
    "updated_at": "2025-08-21T00:00:00.000000Z",
    "roles": ["app user"],
    "permissions": ["permission1", "permission2"],
    "email_verified": true
  }
}
```

**Response (Error):**
```json
{
  "status": "error",
  "message": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## Flutter Implementation Overview

### Required Dependencies

- **google_sign_in**: For Google Sign-In functionality
- **flutter_facebook_auth**: For Facebook Login functionality
- **http**: For API requests to the backend
- **flutter_secure_storage**: For securely storing JWT tokens

### Implementation Flow

1. **Setup OAuth Credentials**:
   - Create Google and Facebook developer accounts
   - Configure OAuth clients for each platform (Android & iOS)
   - Add required configuration to Android and iOS project files

2. **Google Sign-In Flow**:
   - Initialize Google Sign-In with required scopes (email, profile)
   - Trigger the Google sign-in flow and obtain an ID token
   - Send the ID token to the backend API (`/api/v1/auth/google`)
   - Parse the response and extract JWT token and user data

3. **Facebook Login Flow**:
   - Initialize Facebook SDK with required permissions (email, public_profile)
   - Trigger the Facebook login flow and obtain an access token
   - Send the access token to the backend API (`/api/v1/auth/facebook`)
   - Parse the response and extract JWT token and user data

4. **Token Management**:
   - Store the JWT token securely using flutter_secure_storage
   - Add the JWT token to Authorization headers for authenticated API requests
   - Handle token expiration and refreshing as needed

## Key Configuration Requirements

### Google Sign-In

- Google Developer Console project with Sign-In API enabled
- OAuth client IDs for Android and iOS
- SHA-1 certificate fingerprint for Android
- Proper bundle ID configuration for iOS

### Facebook Login

- Facebook Developer account with an app created
- App configured with Android and iOS platforms
- Bundle ID and package name registered
- Facebook app ID and client token configured in the native code

## Common Issues and Troubleshooting

### Google Sign-In

- SHA-1 certificate fingerprint must match what's in the Google API Console
- Package name in AndroidManifest.xml must match the registered package name
- Ensure google-services.json and GoogleService-Info.plist are properly configured

### Facebook Login

- Facebook App ID must be correct in all configuration files
- Bundle ID/Package Name must match exactly what's registered in the Facebook Developer Console
- Facebook app should be "Live" for production use

### JWT Token

- Always store tokens securely
- Handle token expiry appropriately
- Ensure proper token format in Authorization headers
