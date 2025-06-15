# Spotlight API Documentation

## Overview

This document provides comprehensive information about the Spotlight API endpoints, including authentication, filtering capabilities, and response formats.

## Base URL

All API endpoints are prefixed with `/api/v1/`.

## Authentication

### Web Authentication (Sanctum)

Protected endpoints for web admin panel require authentication via Laravel Sanctum. Include your API token in the request header:

```
Authorization: Bearer YOUR_API_TOKEN
```

### Mobile Authentication (JWT)

Mobile applications should use JWT authentication. JWT provides a stateless, token-based authentication mechanism suitable for mobile clients.

#### Obtaining a JWT Token

**Endpoint:** `POST /api/v1/auth/login`

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {...},
  "roles": [...],
  "permissions": [...]
}
```

#### Using JWT Authentication

Include the JWT token in the Authorization header for all protected requests:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

## Mobile Authentication Endpoints

### Register a New User

**Endpoint:** `POST /api/v1/auth/register`

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2023-06-15T12:34:56.000000Z",
    "updated_at": "2023-06-15T12:34:56.000000Z"
  },
  "roles": ["app user"],
  "permissions": [],
  "message": "User successfully registered"
}
```

### Get User Profile

**Endpoint:** `GET /api/v1/auth/me`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2023-06-15T12:34:56.000000Z",
    "updated_at": "2023-06-15T12:34:56.000000Z"
  },
  "roles": ["app user"],
  "permissions": ["view spotlights", "create comments"]
}
```

### Refresh Token

**Endpoint:** `POST /api/v1/auth/refresh`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "access_token": "NEW_JWT_TOKEN",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {...},
  "roles": [...],
  "permissions": [...]
}
```

### Logout

**Endpoint:** `POST /api/v1/auth/logout`

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "message": "Successfully logged out"
}
```

## Public Endpoints

### Categories

#### List All Categories

**Endpoint:** `GET /api/v1/categories`

**Description:** Retrieves a paginated list of all active spotlight categories.

**Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Restaurants",
      "slug": "restaurants",
      "icon": null,
      "description": "Restaurants in the area",
      "is_active": true,
      "parent_id": null
    },
    ...
  ],
  "links": {...},
  "meta": {...}
}
```

#### Get Category Details

**Endpoint:** `GET /api/v1/categories/{category}`

**Description:** Retrieves detailed information about a specific category.

**Parameters:**
- `category`: Category ID

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Restaurants",
    "slug": "restaurants",
    "icon": null,
    "description": "Restaurants in the area",
    "is_active": true,
    "parent_id": null,
    "subcategories": [...]
  }
}
```

#### Get Category Attributes

**Endpoint:** `GET /api/v1/categories/{category}/attributes`

**Description:** Retrieves the attributes associated with a specific category.

**Parameters:**
- `category`: Category ID

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "key": "cuisine",
      "name": "Cuisine Type",
      "type": "select",
      "display_type": "select",
      "options": [
        {"id": 1, "value": "lebanese", "label": "Lebanese"},
        {"id": 2, "value": "italian", "label": "Italian"}
      ]
    },
    ...
  ]
}
```

#### Get Category Filters

**Endpoint:** `GET /api/v1/categories/{category}/filters`

**Description:** Retrieves the filterable attributes for a specific category.

**Parameters:**
- `category`: Category ID

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "key": "cuisine",
      "name": "Cuisine Type",
      "type": "select",
      "display_type": "select",
      "options": [
        {"id": 1, "value": "lebanese", "label": "Lebanese"},
        {"id": 2, "value": "italian", "label": "Italian"}
      ]
    },
    ...
  ]
}
```

### Spotlights

#### List All Spotlights

**Endpoint:** `GET /api/v1/spotlights`

**Description:** Retrieves a paginated list of all active spotlights with comprehensive filtering options.

**Filtering Parameters:**
- `category_id` (optional): Filter by category ID
- `tag_id` (optional): Filter by tag ID
- `location_id` (optional): Filter by location ID
- `search` (optional): Search term for name and description
- `attributes[key]` (optional): Filter by attribute value (can include multiple attributes)
- `sort_by` (optional): Field to sort by (default: created_at)
- `sort_dir` (optional): Sort direction (asc or desc, default: desc)
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Example Requests:**
```
/api/v1/spotlights?category_id=1&location_id=2
/api/v1/spotlights?attributes[cuisine]=lebanese&tag_id=5
/api/v1/spotlights?search=cafe&sort_by=rating&sort_dir=desc
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Example Spotlight",
      "description": "Sample description",
      "category": {
        "id": 1,
        "name": "Restaurants",
        ...
      },
      "location": {
        "id": 2,
        "name": "Downtown",
        ...
      },
      "tags": [...],
      "rating": 4.5,
      "is_active": true,
      "is_featured": false,
      "contact_email": "example@example.com",
      "contact_phone": "+1234567890",
      "website_url": "https://example.com"
    },
    ...
  ],
  "links": {...},
  "meta": {...}
}
```

#### Get Spotlight Details

**Endpoint:** `GET /api/v1/spotlights/{spotlight}`

**Description:** Retrieves detailed information about a specific spotlight.

**Parameters:**
- `spotlight`: Spotlight ID

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Example Spotlight",
    "description": "Sample description",
    "category": {...},
    "location": {...},
    "tags": [...],
    "rating": 4.5,
    "is_active": true,
    "is_featured": false,
    "contact_email": "example@example.com",
    "contact_phone": "+1234567890",
    "website_url": "https://example.com"
  }
}
```

#### Get Featured Spotlights

**Endpoint:** `GET /api/v1/spotlights/featured`

**Description:** Retrieves a paginated list of featured spotlights.

**Parameters:**
- `per_page` (optional): Number of items per page (default: 8)
- `page` (optional): Page number (default: 1)

**Response:** Same format as List All Spotlights

#### Get Spotlights by Category

**Endpoint:** `GET /api/v1/spotlights/category/{category}`

**Description:** Retrieves a paginated list of spotlights by category (including subcategories).

**Parameters:**
- `category`: Category ID
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:** Same format as List All Spotlights

#### Get Spotlight Attributes

**Endpoint:** `GET /api/v1/spotlights/{spotlight}/attributes`

**Description:** Retrieves the attribute values for a specific spotlight.

**Parameters:**
- `spotlight`: Spotlight ID

**Response:**
```json
{
  "data": [
    {
      "key": "cuisine",
      "name": "Cuisine Type",
      "value": "Lebanese"
    },
    ...
  ]
}
```

### Tags

#### List All Tags

**Endpoint:** `GET /api/v1/tags`

**Description:** Retrieves a list of all tags.

**Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Family Friendly",
      "slug": "family-friendly",
      "description": null
    },
    ...
  ],
  "links": {...},
  "meta": {...}
}
```

#### Get Spotlights by Tag

**Endpoint:** `GET /api/v1/tags/{tag}/spotlights`

**Description:** Retrieves a paginated list of spotlights that have the specified tag.

**Parameters:**
- `tag`: Tag ID
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:** Same format as List All Spotlights

### Locations

#### List All Locations

**Endpoint:** `GET /api/v1/locations`

**Description:** Retrieves a paginated list of all locations.

**Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Downtown",
      "description": "Downtown area",
      "latitude": 33.8938,
      "longitude": 35.5018
    },
    ...
  ],
  "links": {...},
  "meta": {...}
}
```

#### Get Spotlights by Location

**Endpoint:** `GET /api/v1/locations/{location}/spotlights`

**Description:** Retrieves a paginated list of spotlights in a specific location.

**Parameters:**
- `location`: Location ID
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:** Same format as List All Spotlights

### Attribute Definitions

#### List All Attribute Definitions

**Endpoint:** `GET /api/v1/attributes`

**Description:** Retrieves a paginated list of spotlight attribute definitions.

**Parameters:**
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "key": "cuisine",
      "name": "Cuisine Type",
      "type": "select",
      "display_type": "select",
      "options": [...]
    },
    ...
  ],
  "links": {...},
  "meta": {...}
}
```

#### Get Attribute Definition Details

**Endpoint:** `GET /api/v1/attributes/{attribute}`

**Description:** Retrieves details of a specific attribute definition.

**Parameters:**
- `attribute`: Attribute ID

**Response:**
```json
{
  "data": {
    "id": 1,
    "key": "cuisine",
    "name": "Cuisine Type",
    "type": "select",
    "display_type": "select",
    "options": [...]
  }
}
```

## Protected Endpoints

The following endpoints require authentication and appropriate permissions:

### Spotlight Management

#### Create Spotlight

**Endpoint:** `POST /api/v1/spotlights`

**Permission:** `create spotlight`

**Request Body:**
```json
{
  "name": "New Spotlight",
  "description": "Detailed description",
  "category_id": 1,
  "location_id": 2,
  "is_active": true,
  "rating": 4.5,
  "contact_email": "contact@example.com",
  "contact_phone": "+1234567890",
  "website_url": "https://example.com",
  "tags": [1, 3, 5],
  "attributes": {
    "cuisine": "italian",
    "price_range": "moderate"
  }
}
```

**Response:** Newly created spotlight object

#### Update Spotlight

**Endpoint:** `PUT /api/v1/spotlights/{spotlight}`

**Permission:** `update spotlight`

**Request Body:** Same format as Create Spotlight (all fields optional)

**Response:** Updated spotlight object

#### Delete Spotlight

**Endpoint:** `DELETE /api/v1/spotlights/{spotlight}`

**Permission:** `delete spotlight`

**Response:** Success message

#### Publish Spotlight

**Endpoint:** `PATCH /api/v1/spotlights/{spotlight}/publish`

**Permission:** `publish spotlight`

**Response:** Updated spotlight object with `is_active` set to true

#### Unpublish Spotlight

**Endpoint:** `PATCH /api/v1/spotlights/{spotlight}/unpublish`

**Permission:** `publish spotlight`

**Response:** Updated spotlight object with `is_active` set to false

## Caching

Many API responses are cached for improved performance. Cache invalidation happens automatically when related data is updated.

## Error Handling

The API returns standard HTTP status codes:

- `200 OK`: Request succeeded
- `201 Created`: Resource was successfully created
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation error
- `500 Internal Server Error`: Server-side error

Error responses include details about the error:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."]
  }
}
```
