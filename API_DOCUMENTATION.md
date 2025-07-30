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
  "login": "user@example.com", // Can be email or username
  "password": "password"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Successfully logged in",
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {...},
  "roles": [...],
  "permissions": [...]
}
```

**Error Response (401 Unauthorized):**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

**Error Response (422 Validation Error):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
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
  "status": "success",
  "message": "User successfully registered",
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
  "permissions": []
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
  "status": "success",
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

**Error Response (401 Unauthorized):**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```
```

### Update User Profile

**Endpoint:** `PUT /api/v1/auth/update-profile`

**Description:** Updates the authenticated user's profile information. Uses the same fields as registration, with password being optional.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request:**
```json
{
  "name": "John Smith",
  "email": "johnsmith@example.com",
  "password": "new_password",
  "password_confirmation": "new_password",
  "mobile": "+9715123456789",
  "address": "123 Main Street, Dubai, UAE"
}
```

**Notes:**
- `password` and `password_confirmation` are optional. If not provided, the password will remain unchanged.
- `mobile` and `address` are optional fields.
- Email must be unique, except for the current user's email.

**Successful Response (200 OK):**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "user": {
    "id": 1,
    "name": "John Smith",
    "email": "johnsmith@example.com",
    "mobile": "+9715123456789",
    "address": "123 Main Street, Dubai, UAE",
    "created_at": "2023-06-15T12:34:56.000000Z",
    "updated_at": "2023-07-01T22:12:45.000000Z"
  },
  "roles": ["app user"],
  "permissions": ["view spotlights", "create comments"]
}
```

**Error Responses:**

*Validation error (422 Unprocessable Entity):*
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email has already been taken."]
  }
}
```

*Unauthorized (401 Unauthorized):*
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

*Server error (500 Internal Server Error):*
```json
{
  "status": "error",
  "message": "Failed to update profile: Error details..."
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
  "status": "success",
  "access_token": "NEW_JWT_TOKEN",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {...},
  "roles": [...],
  "permissions": [...]
}
```

**Error Response (401 Unauthorized):**
```json
{
  "status": "error",
  "message": "Unauthorized"
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
  "status": "success",
  "message": "Successfully logged out"
}
```

### Delete Account

**Endpoint:** `DELETE /api/v1/auth/delete-account`

**Description:** Permanently deletes the authenticated user's account. This action cannot be undone. For security reasons, the user must confirm their password.  

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Request:**
```json
{
  "password": "current_password"
}
```

**Successful Response (200 OK):**
```json
{
  "status": "success",
  "message": "Account successfully deleted"
}
```

**Error Responses:**

*Validation error (422 Unprocessable Entity):*
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "password": ["The password field is required."]
  }
}
```

*Incorrect password (422 Unprocessable Entity):*
```json
{
  "status": "error",
  "message": "The provided password is incorrect"
}
```

*Unauthorized (401 Unauthorized):*
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

*Server error (500 Internal Server Error):*
```json
{
  "status": "error",
  "message": "Failed to delete account: An unexpected error occurred"
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
- `location` (optional): Filter categories by home screen location slug (e.g., 'top', 'middle', 'bottom')
- `include_inactive` (optional): Include inactive categories (default: false)
- `with_attributes` (optional): Include attribute definitions for each category (default: false)
- `hierarchical` (optional): Return categories in a hierarchical tree structure (default: false)

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

#### Get Categories by Home Screen Location

**Endpoint:** `GET /api/v1/categories/location/{locationSlug}`

**Description:** Retrieves spotlight categories assigned to a specific home screen location (top, middle, bottom, etc.).

**Parameters:**
- `locationSlug`: The slug of the home screen location (e.g., 'top', 'middle', 'bottom')
- `include_inactive` (optional): Include inactive categories (default: false)
- `with_attributes` (optional): Include attribute definitions for each category (default: false)
- `hierarchical` (optional): Return categories in a hierarchical tree structure (default: false)

**Response:**
```json
{
  "status": "success",
  "location": "top",
  "categories": [
    {
      "id": 1,
      "name": "Restaurants",
      "slug": "restaurants",
      "icon": null,
      "description": "Restaurants in the area",
      "is_active": true,
      "parent_id": null,
      "home_screen_location_id": 1,
      "home_screen_location": {
        "id": 1,
        "name": "Top",
        "slug": "top",
        "display_order": 1
      }
    },
    ...
  ]
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
- `is_trending` (optional): Filter by trending status (true/false)
- `is_featured` (optional): Filter by featured status (true/false)
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
/api/v1/spotlights?category_id=3&is_featured=false
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
      "is_trending": false,
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

#### Get Trending Spotlights

**Endpoint:** `GET /api/v1/spotlights/trending`

**Description:** Retrieves a paginated list of trending spotlights.

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
- `category_id` (optional): Filter tags by category ID
- `type` (optional): Filter tags by type (general, amenity, cuisine, etc.)
```json
{
  "data": [
 {
        "id": 1,
        "name": "Breakfast",
        "slug": "breakfast",
        "type": "general",
        "color": null,
        "display_order": 0,
        "created_at": "2025-06-14T17:46:53.000000Z",
        "updated_at": "2025-07-03T14:38:17.000000Z",
        "category_id": 1
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

### Saved Spotlights

#### List Saved Spotlights

**Endpoint:** `GET /api/v1/saved-spotlights`

**Authentication:** Required (JWT)

**Description:** Retrieves a paginated list of spotlights saved by the authenticated user.

**Parameters:**
- `per_page` (optional): Number of items per page (default: 10)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Restaurant Name",
        "slug": "restaurant-name",
        "description": "Description of the spotlight",
        "short_description": "Short description",
        "category_id": 1,
        "location_id": 2,
        "user_id": 1,
        "contact_info": {
          "phone": "+1234567890",
          "email": "contact@example.com"
        },
        "social_links": {
          "facebook": "https://facebook.com/example",
          "instagram": "https://instagram.com/example"
        },
        "opening_hours": {
          "monday": "9:00 AM - 5:00 PM",
          "tuesday": "9:00 AM - 5:00 PM"
        },
        "featured_image": "spotlights/featured-image.jpg",
        "is_featured": true,
        "is_trending": false,
        "is_published": true,
        "published_at": "2025-06-01T10:00:00.000000Z",
        "created_at": "2025-06-01T09:00:00.000000Z",
        "updated_at": "2025-06-01T09:00:00.000000Z",
        "category": {
          "id": 1,
          "name": "Restaurants",
          "slug": "restaurants"
        },
        "location": {
          "id": 2,
          "name": "Downtown",
          "slug": "downtown"
        }
      }
    ],
    "first_page_url": "http://localhost/api/v1/saved-spotlights?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost/api/v1/saved-spotlights?page=1",
    "links": [...],
    "next_page_url": null,
    "path": "http://localhost/api/v1/saved-spotlights",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

#### Save a Spotlight

**Endpoint:** `POST /api/v1/saved-spotlights/{id}`

**Authentication:** Required (JWT)

**Description:** Saves a spotlight for the authenticated user.

**Parameters:**
- `id`: Spotlight ID to save

**Response:**
```json
{
  "status": "success",
  "message": "Spotlight saved successfully",
  "data": {
    "user_id": 1,
    "spotlight_id": 5,
    "updated_at": "2025-06-20T02:25:00.000000Z",
    "created_at": "2025-06-20T02:25:00.000000Z",
    "id": 1
  }
}
```

#### Unsave a Spotlight

**Endpoint:** `DELETE /api/v1/saved-spotlights/{id}`

**Authentication:** Required (JWT)

**Description:** Removes a spotlight from the authenticated user's saved spotlights.

**Parameters:**
- `id`: Spotlight ID to unsave

**Response:**
```json
{
  "status": "success",
  "message": "Spotlight unsaved successfully"
}
```

#### Check if Spotlight is Saved

**Endpoint:** `GET /api/v1/saved-spotlights/{id}/check`

**Authentication:** Required (JWT)

**Description:** Checks if a spotlight is saved by the authenticated user.

**Parameters:**
- `id`: Spotlight ID to check

**Response:**
```json
{
  "status": "success",
  "data": {
    "is_saved": true
  }
}
```

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

## News API

### News Categories

#### List News Categories

**Endpoint:** `GET /api/v1/news/categories`

**Description:** Retrieves a list of all active news categories.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "description": "Technology related news",
      "is_active": true,
      "created_at": "2025-06-15T10:00:00.000000Z",
      "updated_at": "2025-06-15T10:00:00.000000Z",
      "news_count": 15
    },
    {
      "id": 2,
      "name": "Business",
      "slug": "business",
      "description": "Business related news",
      "is_active": true,
      "created_at": "2025-06-15T10:05:00.000000Z",
      "updated_at": "2025-06-15T10:05:00.000000Z",
      "news_count": 8
    }
  ]
}
```

#### Get News Category Details

**Endpoint:** `GET /api/v1/news/categories/{id}`

**Description:** Retrieves detailed information about a specific news category and its latest news articles. The `id` parameter can be either the numeric ID or the slug of the category.

**Response:**
```json
{
  "status": "success",
  "data": {
    "category": {
      "id": 1,
      "name": "Technology",
      "slug": "technology",
      "description": "Technology related news",
      "is_active": true,
      "created_at": "2025-06-15T10:00:00.000000Z",
      "updated_at": "2025-06-15T10:00:00.000000Z",
      "news_count": 15
    },
    "latest_news": [
      {
        "id": 1,
        "title": "Latest Tech News",
        "slug": "latest-tech-news",
        "summary": "Brief summary of tech news",
        "featured_image": "news/tech.jpg",
        "published_at": "2025-06-15T18:00:00.000000Z",
        "user_id": 1,
        "author": {
          "id": 1,
          "name": "Author Name"
        }
      }
    ]
  }
}
```

#### Create News Category (Protected)

**Endpoint:** `POST /api/v1/news/categories`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated

**Request:**
```json
{
  "name": "New Category",
  "description": "Description of the new category"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Category created successfully",
  "data": {
    "id": 3,
    "name": "New Category",
    "slug": "new-category",
    "description": "Description of the new category",
    "is_active": true,
    "created_at": "2025-06-15T13:00:00.000000Z",
    "updated_at": "2025-06-15T13:00:00.000000Z"
  }
}
```

#### Update News Category (Protected)

**Endpoint:** `PUT /api/v1/news/categories/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated

**Request:**
```json
{
  "name": "Updated Category Name",
  "description": "Updated category description",
  "is_active": true
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Category updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Category Name",
    "slug": "updated-category-name",
    "description": "Updated category description",
    "is_active": true,
    "created_at": "2025-06-15T10:00:00.000000Z",
    "updated_at": "2025-06-15T13:30:00.000000Z"
  }
}
```

#### Delete News Category (Protected)

**Endpoint:** `DELETE /api/v1/news/categories/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated

**Note:** Categories with associated news articles cannot be deleted.

**Response:**
```json
{
  "status": "success",
  "message": "Category deleted successfully"
}
```

### News Articles

#### List News Articles

**Endpoint:** `GET /api/v1/news`

**Description:** Retrieves a paginated list of published news articles.

**Parameters:**
- `category_id` (optional): Filter news by category ID
- `category_slug` (optional): Filter news by category slug
- `search` (optional): Search term to filter news by title, content, or summary
- `per_page` (optional): Number of items per page (default: 10)
- `page` (optional): Page number for pagination

**Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "News Article Title",
        "slug": "news-article-title",
        "summary": "Brief summary of the news article",
        "content": "Full content of the news article...",
        "featured_image": "news/image.jpg",
        "news_category_id": 1,
        "user_id": 1,
        "is_published": true,
        "published_at": "2025-06-15T12:00:00.000000Z",
        "created_at": "2025-06-15T10:30:00.000000Z",
        "updated_at": "2025-06-15T10:30:00.000000Z",
        "category": {
          "id": 1,
          "name": "Category Name",
          "slug": "category-name"
        },
        "author": {
          "id": 1,
          "name": "Author Name"
        }
      }
    ],
    "first_page_url": "http://localhost/api/v1/news?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://localhost/api/v1/news?page=5",
    "next_page_url": "http://localhost/api/v1/news?page=2",
    "path": "http://localhost/api/v1/news",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 50
  }
}
```

#### Get Latest News

**Endpoint:** `GET /api/v1/news/latest`

**Description:** Retrieves the most recent published news articles, sorted by published date in descending order.

**Parameters:**
- `limit` (optional): Number of news articles to return (default: 5)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Latest News Article",
      "slug": "latest-news-article",
      "summary": "Brief summary of the latest news",
      "content": "Full content of the news article...",
      "featured_image": "news/latest.jpg",
      "news_category_id": 1,
      "user_id": 1,
      "is_published": true,
      "is_featured": false,
      "published_at": "2025-06-19T18:00:00.000000Z",
      "created_at": "2025-06-19T15:30:45.000000Z",
      "updated_at": "2025-06-19T15:30:45.000000Z",
      "category": {
        "id": 1,
        "name": "Category Name",
        "slug": "category-name"
      },
      "author": {
        "id": 1,
        "name": "Author Name"
      }
    }
  ]
}
```

#### Get Featured News

**Endpoint:** `GET /api/v1/news/featured`

**Description:** Retrieves featured news articles (those with the `is_featured` flag set to true).

**Parameters:**
- `limit` (optional): Number of featured news articles to return (default: 5)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Featured News Article",
      "slug": "featured-news-article",
      "summary": "Brief summary of the featured news",
      "content": "Full content of the featured news article...",
      "featured_image": "news/featured.jpg",
      "news_category_id": 1,
      "user_id": 1,
      "is_published": true,
      "is_featured": true,
      "published_at": "2025-06-19T12:00:00.000000Z",
      "created_at": "2025-06-19T10:30:45.000000Z",
      "updated_at": "2025-06-19T10:30:45.000000Z",
      "category": {
        "id": 1,
        "name": "Category Name",
        "slug": "category-name"
      },
      "author": {
        "id": 1,
        "name": "Author Name"
      }
    }
  ]
}
```

#### Get News Article Details

**Endpoint:** `GET /api/v1/news/{id}`

**Description:** Retrieves detailed information about a specific news article. The `id` parameter can be either the numeric ID or the slug of the news article.

**Response:**
```json
{
  "status": "success",
  "data": {
    "news": {
      "id": 1,
      "title": "News Article Title",
      "slug": "news-article-title",
      "summary": "Brief summary of the news article",
      "content": "Full content of the news article...",
      "featured_image": "news/image.jpg",
      "news_category_id": 1,
      "user_id": 1,
      "is_published": true,
      "published_at": "2025-06-15T12:00:00.000000Z",
      "created_at": "2025-06-15T10:30:00.000000Z",
      "updated_at": "2025-06-15T10:30:00.000000Z",
      "category": {
        "id": 1,
        "name": "Category Name",
        "slug": "category-name"
      },
      "author": {
        "id": 1,
        "name": "Author Name"
      }
    },
    "related_news": [
      {
        "id": 2,
        "title": "Related News Article",
        "slug": "related-news-article",
        "summary": "Brief summary of related news",
        "featured_image": "news/related.jpg",
        "published_at": "2025-06-14T12:00:00.000000Z"
      }
    ]
  }
}
```

#### Create News Article (Protected)

**Endpoint:** `POST /api/v1/news`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated

**Request:**
```json
{
  "title": "New Article Title",
  "summary": "Brief summary of the article",
  "content": "Full content of the article...",
  "news_category_id": 1,
  "is_published": true,
  "published_at": "2025-06-15T12:00:00.000000Z"
}
```

**Note:** For `featured_image`, use multipart/form-data to upload the image file.

**Response:**
```json
{
  "status": "success",
  "message": "News article created successfully",
  "data": {
    "id": 3,
    "title": "New Article Title",
    "slug": "new-article-title",
    "summary": "Brief summary of the article",
    "content": "Full content of the article...",
    "featured_image": "news/uploaded-image.jpg",
    "news_category_id": 1,
    "user_id": 1,
    "is_published": true,
    "published_at": "2025-06-15T12:00:00.000000Z",
    "created_at": "2025-06-15T11:30:00.000000Z",
    "updated_at": "2025-06-15T11:30:00.000000Z",
    "category": {
      "id": 1,
      "name": "Category Name",
      "slug": "category-name"
    },
    "author": {
      "id": 1,
      "name": "Author Name"
    }
  }
}
```

#### Update News Article (Protected)

**Endpoint:** `PUT /api/v1/news/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated and either be the author of the article or have admin role

**Request:**
```json
{
  "title": "Updated Article Title",
  "summary": "Updated brief summary",
  "content": "Updated full content...",
  "news_category_id": 2,
  "is_published": true,
  "published_at": "2025-06-15T14:00:00.000000Z"
}
```

**Note:** For `featured_image`, use multipart/form-data to upload a new image file.

**Response:**
```json
{
  "status": "success",
  "message": "News article updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Article Title",
    "slug": "updated-article-title",
    "summary": "Updated brief summary",
    "content": "Updated full content...",
    "featured_image": "news/new-image.jpg",
    "news_category_id": 2,
    "user_id": 1,
    "is_published": true,
    "published_at": "2025-06-15T14:00:00.000000Z",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T12:45:00.000000Z",
    "category": {
      "id": 2,
      "name": "New Category",
      "slug": "new-category"
    },
    "author": {
      "id": 1,
      "name": "Author Name"
    }
  }
}
```

#### Delete News Article (Protected)

**Endpoint:** `DELETE /api/v1/news/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated and either be the author of the article or have admin role

**Response:**
```json
{
  "status": "success",
  "message": "News article deleted successfully"
}
```

## Banner Management

These endpoints allow for management of banner advertisements throughout the application.

## About Us Management

These endpoints allow for retrieving and managing the About Us page content.

### Get About Us Content

**Endpoint:** `GET /api/v1/about-us`

**Authentication:** Not required

**Description:** Retrieves the About Us page content including image, text blocks, and slogan.

**Response:**
```json
{
  "data": {
    "id": 1,
    "image": "https://example.com/storage/about-us/about-image.jpg",
    "text1": "<p>Main content for the About Us page.</p>",
    "text2": "<p>Additional information about the company.</p>",
    "text3": "<p>More details about services or history.</p>",
    "slogan": "Your Trusted Partner",
    "created_at": "2025-06-20T23:55:00.000000Z",
    "updated_at": "2025-06-21T01:30:00.000000Z"
  }
}
```

**Note:** If no About Us content exists yet, the response will contain null values for all fields.

### Update About Us Content (Protected)

**Endpoint:** `POST /api/v1/about-us/update`

**Authentication:** Required (JWT)

**Permissions:** User must have 'manage about us' permission

**Request Format:** `multipart/form-data`

**Request Parameters:**
- `image` (optional): Image file (JPEG, PNG, JPG, max 2MB)
- `text1` (optional): Main content text (HTML supported)
- `text2` (optional): Secondary content text (HTML supported)
- `text3` (optional): Additional content text (HTML supported)
- `slogan` (optional): Short slogan or tagline (max 255 characters)

**Response:**
```json
{
  "message": "About Us content updated successfully",
  "data": {
    "id": 1,
    "image": "https://example.com/storage/about-us/about-image.jpg",
    "text1": "<p>Updated main content for the About Us page.</p>",
    "text2": "<p>Updated additional information about the company.</p>",
    "text3": "<p>Updated details about services or history.</p>",
    "slogan": "Your Trusted Partner Since 2025",
    "created_at": "2025-06-20T23:55:00.000000Z",
    "updated_at": "2025-06-21T02:15:00.000000Z"
  }
}
```

**Note:** For the `image` field, use multipart/form-data to upload the image file. The API will handle both creating new content if none exists and updating existing content.

## Ad Management

These endpoints allow for management of targeted advertisements throughout the application.

## Contact Us API

These endpoints allow users to submit contact form messages and administrators to manage them.

### Submit Contact Form

**Endpoint:** `POST /api/v1/contact`

**Description:** Allows users to submit a contact form message.

**Request:**
```json
{
  "full_name": "John Doe",
  "email": "john@example.com",
  "mobile": "+1234567890",  // Optional
  "subject": "General Inquiry",
  "message": "I would like more information about your services."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Your message has been sent successfully. We will contact you soon.",
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john@example.com",
    "mobile": "+1234567890",
    "subject": "General Inquiry",
    "message": "I would like more information about your services.",
    "is_read": false,
    "read_at": null,
    "created_at": "2025-06-21T00:35:12.000000Z",
    "updated_at": "2025-06-21T00:35:12.000000Z"
  }
}
```

### List Contact Messages (Admin Only)

**Endpoint:** `GET /api/v1/contact-messages`

**Description:** Retrieves a paginated list of all contact form submissions.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "full_name": "John Doe",
        "email": "john@example.com",
        "mobile": "+1234567890",
        "subject": "General Inquiry",
        "message": "I would like more information about your services.",
        "is_read": false,
        "read_at": null,
        "created_at": "2025-06-21T00:35:12.000000Z",
        "updated_at": "2025-06-21T00:35:12.000000Z"
      },
      // More messages...
    ],
    "first_page_url": "http://example.com/api/v1/contact-messages?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://example.com/api/v1/contact-messages?page=3",
    "links": [...],
    "next_page_url": "http://example.com/api/v1/contact-messages?page=2",
    "path": "http://example.com/api/v1/contact-messages",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 42
  }
}
```

### View Contact Message (Admin Only)

**Endpoint:** `GET /api/v1/contact-messages/{id}`

**Description:** Retrieves details of a specific contact message.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Parameters:**
- `id`: Contact message ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john@example.com",
    "mobile": "+1234567890",
    "subject": "General Inquiry",
    "message": "I would like more information about your services.",
    "is_read": false,
    "read_at": null,
    "created_at": "2025-06-21T00:35:12.000000Z",
    "updated_at": "2025-06-21T00:35:12.000000Z"
  }
}
```

### Mark Message as Read (Admin Only)

**Endpoint:** `PATCH /api/v1/contact-messages/{id}/mark-read`

**Description:** Marks a contact message as read.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Parameters:**
- `id`: Contact message ID

**Response:**
```json
{
  "success": true,
  "message": "Message marked as read",
  "data": {
    "id": 1,
    "full_name": "John Doe",
    "email": "john@example.com",
    "mobile": "+1234567890",
    "subject": "General Inquiry",
    "message": "I would like more information about your services.",
    "is_read": true,
    "read_at": "2025-06-21T00:45:23.000000Z",
    "created_at": "2025-06-21T00:35:12.000000Z",
    "updated_at": "2025-06-21T00:45:23.000000Z"
  }
}
```

### Delete Contact Message (Admin Only)

**Endpoint:** `DELETE /api/v1/contact-messages/{id}`

**Description:** Deletes a contact message.

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Parameters:**
- `id`: Contact message ID

**Response:**
```json
{
  "success": true,
  "message": "Message deleted successfully"
}
```

### Ad Locations

#### Get Ad Locations

**Endpoint:** `GET /api/v1/ad-locations`

**Description:** Retrieves a list of all ad locations with their associated ad counts.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Sidebar Top",
      "description": "Ad placement at the top of the sidebar",
      "created_at": "2025-06-15T10:30:00.000000Z",
      "updated_at": "2025-06-15T10:30:00.000000Z",
      "ads_count": 3
    },
    {
      "id": 2,
      "name": "Article Footer",
      "description": "Ad displayed at the bottom of articles",
      "created_at": "2025-06-15T10:30:00.000000Z",
      "updated_at": "2025-06-15T10:30:00.000000Z",
      "ads_count": 1
    }
  ]
}
```

#### Get Ad Location Details

**Endpoint:** `GET /api/v1/ad-locations/{id}`

**Description:** Retrieves detailed information about a specific ad location including its active ads.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "Sidebar Top",
    "description": "Ad placement at the top of the sidebar",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T10:30:00.000000Z",
    "active_ads": [
      {
        "id": 1,
        "title": "Premium Membership",
        "image": "ads/premium-membership.jpg",
        "link_url": "https://example.com/premium",
        "is_active": true,
        "display_order": 1,
        "start_date": "2025-06-01T00:00:00.000000Z",
        "end_date": "2025-08-31T23:59:59.000000Z"
      },
      {
        "id": 2,
        "title": "Special Offer",
        "image": "ads/special-offer.jpg",
        "link_url": "https://example.com/offer",
        "is_active": true,
        "display_order": 2,
        "start_date": "2025-06-15T00:00:00.000000Z",
        "end_date": "2025-07-15T23:59:59.000000Z"
      }
    ]
  }
}
```

#### Create Ad Location (Protected)

**Endpoint:** `POST /api/v1/ad-locations`

**Authentication:** Required (JWT)

**Permissions:** User must have admin role

**Request:**
```json
{
  "name": "Mobile App Banner",
  "description": "Ad displayed in the mobile application"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Ad location created successfully",
  "data": {
    "id": 3,
    "name": "Mobile App Banner",
    "description": "Ad displayed in the mobile application",
    "created_at": "2025-06-15T13:45:00.000000Z",
    "updated_at": "2025-06-15T13:45:00.000000Z"
  }
}
```

#### Update Ad Location (Protected)

**Endpoint:** `PUT /api/v1/ad-locations/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must have admin role

**Request:**
```json
{
  "name": "Updated Location Name",
  "description": "Updated location description"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Ad location updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Location Name",
    "description": "Updated location description",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T14:20:00.000000Z"
  }
}
```

#### Delete Ad Location (Protected)

**Endpoint:** `DELETE /api/v1/ad-locations/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must have admin role

**Note:** Ad locations with associated ads cannot be deleted.

**Response:**
```json
{
  "status": "success",
  "message": "Ad location deleted successfully"
}
```

### Ads

#### Get Active Ads for Location

**Endpoint:** `GET /api/v1/ad-locations/{location_id}/ads`

**Description:** Retrieves all active ads for a specific location by ID, sorted by display order.

#### Get Active Ads for Location by Slug

**Endpoint:** `GET /api/v1/ads/by-location-slug/{slug}`

**Description:** Retrieves all active ads for a specific location by slug, sorted by display order.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Premium Membership",
      "image": "ads/premium-membership.jpg",
      "link_url": "https://example.com/premium",
      "is_active": true,
      "display_order": 1,
      "start_date": "2025-06-01T00:00:00.000000Z",
      "end_date": "2025-08-31T23:59:59.000000Z"
    },
    {
      "id": 2,
      "title": "Special Offer",
      "image": "ads/special-offer.jpg",
      "link_url": "https://example.com/offer",
      "is_active": true,
      "display_order": 2,
      "start_date": "2025-06-15T00:00:00.000000Z",
      "end_date": "2025-07-15T23:59:59.000000Z"
    }
  ]
}
```

#### Get Ad Details

**Endpoint:** `GET /api/v1/ads/{id}`

**Description:** Retrieves detailed information about a specific ad.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Premium Membership",
    "description": "Promotion for premium membership",
    "image": "ads/premium-membership.jpg",
    "link_url": "https://example.com/premium",
    "ad_location_id": 1,
    "user_id": 1,
    "is_active": true,
    "display_order": 1,
    "start_date": "2025-06-01T00:00:00.000000Z",
    "end_date": "2025-08-31T23:59:59.000000Z",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T10:30:00.000000Z",
    "location": {
      "id": 1,
      "name": "Sidebar Top"
    },
    "user": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

#### Create Ad (Protected)

**Endpoint:** `POST /api/v1/ads`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated

**Request:**
```json
{
  "title": "New Ad",
  "description": "Description of the new ad",
  "link_url": "https://example.com/promotion",
  "ad_location_id": 1,
  "is_active": true,
  "display_order": 3,
  "start_date": "2025-07-01T00:00:00.000000Z",
  "end_date": "2025-07-31T23:59:59.000000Z"
}
```

**Note:** For `image`, use multipart/form-data to upload the image file.

**Response:**
```json
{
  "status": "success",
  "message": "Ad created successfully",
  "data": {
    "id": 3,
    "title": "New Ad",
    "description": "Description of the new ad",
    "image": "ads/new-ad.jpg",
    "link_url": "https://example.com/promotion",
    "ad_location_id": 1,
    "user_id": 1,
    "is_active": true,
    "display_order": 3,
    "start_date": "2025-07-01T00:00:00.000000Z",
    "end_date": "2025-07-31T23:59:59.000000Z",
    "created_at": "2025-06-15T15:30:00.000000Z",
    "updated_at": "2025-06-15T15:30:00.000000Z",
    "location": {
      "id": 1,
      "name": "Sidebar Top"
    },
    "user": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

#### Update Ad (Protected)

**Endpoint:** `PUT /api/v1/ads/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated and either be the creator of the ad or have admin role

**Request:**
```json
{
  "title": "Updated Ad",
  "description": "Updated description",
  "link_url": "https://example.com/updated-promotion",
  "ad_location_id": 2,
  "is_active": true,
  "display_order": 1,
  "start_date": "2025-07-15T00:00:00.000000Z",
  "end_date": "2025-08-15T23:59:59.000000Z"
}
```

**Note:** For `image`, use multipart/form-data to upload a new image file.

**Response:**
```json
{
  "status": "success",
  "message": "Ad updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Ad",
    "description": "Updated description",
    "image": "ads/updated-ad.jpg",
    "link_url": "https://example.com/updated-promotion",
    "ad_location_id": 2,
    "user_id": 1,
    "is_active": true,
    "display_order": 1,
    "start_date": "2025-07-15T00:00:00.000000Z",
    "end_date": "2025-08-15T23:59:59.000000Z",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T16:15:00.000000Z",
    "location": {
      "id": 2,
      "name": "Article Footer"
    },
    "user": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

#### Delete Ad (Protected)

**Endpoint:** `DELETE /api/v1/ads/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated and either be the creator of the ad or have admin role

**Response:**
```json
{
  "status": "success",
  "message": "Ad deleted successfully"
}
```

### Banner Locations

#### Get Banner Locations

**Endpoint:** `GET /api/v1/banner-locations`

**Description:** Retrieves a list of all banner locations with their associated banner counts.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Homepage Hero",
      "description": "Large banner at the top of the homepage",
      "created_at": "2025-06-15T10:30:00.000000Z",
      "updated_at": "2025-06-15T10:30:00.000000Z",
      "banners_count": 3
    },
    {
      "id": 2,
      "name": "Sidebar",
      "description": "Banner displayed in the sidebar",
      "created_at": "2025-06-15T10:30:00.000000Z",
      "updated_at": "2025-06-15T10:30:00.000000Z",
      "banners_count": 1
    }
  ]
}
```

#### Get Banner Location Details

**Endpoint:** `GET /api/v1/banner-locations/{id}`

**Description:** Retrieves detailed information about a specific banner location including its active banners.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "Homepage Hero",
    "description": "Large banner at the top of the homepage",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T10:30:00.000000Z",
    "active_banners": [
      {
        "id": 1,
        "title": "Summer Sale",
        "image_url": "banners/summer-sale.jpg",
        "link_url": "https://example.com/summer-sale",
        "is_active": true,
        "display_order": 1,
        "start_date": "2025-06-01T00:00:00.000000Z",
        "end_date": "2025-08-31T23:59:59.000000Z"
      },
      {
        "id": 2,
        "title": "New Collection",
        "image_url": "banners/new-collection.jpg",
        "link_url": "https://example.com/new-collection",
        "is_active": true,
        "display_order": 2,
        "start_date": "2025-06-15T00:00:00.000000Z",
        "end_date": "2025-07-15T23:59:59.000000Z"
      }
    ]
  }
}
```

#### Create Banner Location (Protected)

**Endpoint:** `POST /api/v1/banner-locations`

**Authentication:** Required (JWT)

**Permissions:** User must have admin role

**Request:**
```json
{
  "name": "Product Page Top",
  "description": "Banner displayed at the top of product pages"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Banner location created successfully",
  "data": {
    "id": 3,
    "name": "Product Page Top",
    "description": "Banner displayed at the top of product pages",
    "created_at": "2025-06-15T13:45:00.000000Z",
    "updated_at": "2025-06-15T13:45:00.000000Z"
  }
}
```

#### Update Banner Location (Protected)

**Endpoint:** `PUT /api/v1/banner-locations/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must have admin role

**Request:**
```json
{
  "name": "Updated Location Name",
  "description": "Updated location description"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Banner location updated successfully",
  "data": {
    "id": 1,
    "name": "Updated Location Name",
    "description": "Updated location description",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T14:20:00.000000Z"
  }
}
```

#### Delete Banner Location (Protected)

**Endpoint:** `DELETE /api/v1/banner-locations/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must have admin role

**Note:** Banner locations with associated banners cannot be deleted.

**Response:**
```json
{
  "status": "success",
  "message": "Banner location deleted successfully"
}
```

### Banners

#### Get Active Banners for Location

**Endpoint:** `GET /api/v1/banner-locations/{location_id}/banners`

**Description:** Retrieves all active banners for a specific location, sorted by display order.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Summer Sale",
      "image_url": "banners/summer-sale.jpg",
      "link_url": "https://example.com/summer-sale",
      "is_active": true,
      "display_order": 1,
      "start_date": "2025-06-01T00:00:00.000000Z",
      "end_date": "2025-08-31T23:59:59.000000Z"
    },
    {
      "id": 2,
      "title": "New Collection",
      "image_url": "banners/new-collection.jpg",
      "link_url": "https://example.com/new-collection",
      "is_active": true,
      "display_order": 2,
      "start_date": "2025-06-15T00:00:00.000000Z",
      "end_date": "2025-07-15T23:59:59.000000Z"
    }
  ]
}
```

#### Get Banner Details

**Endpoint:** `GET /api/v1/banners/{id}`

**Description:** Retrieves detailed information about a specific banner.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "Summer Sale",
    "description": "Promotion for summer products",
    "image_url": "banners/summer-sale.jpg",
    "link_url": "https://example.com/summer-sale",
    "banner_location_id": 1,
    "user_id": 1,
    "is_active": true,
    "display_order": 1,
    "start_date": "2025-06-01T00:00:00.000000Z",
    "end_date": "2025-08-31T23:59:59.000000Z",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T10:30:00.000000Z",
    "location": {
      "id": 1,
      "name": "Homepage Hero"
    },
    "user": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

#### Create Banner (Protected)

**Endpoint:** `POST /api/v1/banners`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated

**Request:**
```json
{
  "title": "New Banner",
  "description": "Description of the new banner",
  "link_url": "https://example.com/promotion",
  "banner_location_id": 1,
  "is_active": true,
  "display_order": 3,
  "start_date": "2025-07-01T00:00:00.000000Z",
  "end_date": "2025-07-31T23:59:59.000000Z"
}
```

**Note:** For `image`, use multipart/form-data to upload the image file.

**Response:**
```json
{
  "status": "success",
  "message": "Banner created successfully",
  "data": {
    "id": 3,
    "title": "New Banner",
    "description": "Description of the new banner",
    "image_url": "banners/new-banner.jpg",
    "link_url": "https://example.com/promotion",
    "banner_location_id": 1,
    "user_id": 1,
    "is_active": true,
    "display_order": 3,
    "start_date": "2025-07-01T00:00:00.000000Z",
    "end_date": "2025-07-31T23:59:59.000000Z",
    "created_at": "2025-06-15T15:30:00.000000Z",
    "updated_at": "2025-06-15T15:30:00.000000Z",
    "location": {
      "id": 1,
      "name": "Homepage Hero"
    },
    "user": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

#### Update Banner (Protected)

**Endpoint:** `PUT /api/v1/banners/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated and either be the creator of the banner or have admin role

**Request:**
```json
{
  "title": "Updated Banner",
  "description": "Updated description",
  "link_url": "https://example.com/updated-promotion",
  "banner_location_id": 2,
  "is_active": true,
  "display_order": 1,
  "start_date": "2025-07-15T00:00:00.000000Z",
  "end_date": "2025-08-15T23:59:59.000000Z"
}
```

**Note:** For `image`, use multipart/form-data to upload a new image file.

**Response:**
```json
{
  "status": "success",
  "message": "Banner updated successfully",
  "data": {
    "id": 1,
    "title": "Updated Banner",
    "description": "Updated description",
    "image_url": "banners/updated-banner.jpg",
    "link_url": "https://example.com/updated-promotion",
    "banner_location_id": 2,
    "user_id": 1,
    "is_active": true,
    "display_order": 1,
    "start_date": "2025-07-15T00:00:00.000000Z",
    "end_date": "2025-08-15T23:59:59.000000Z",
    "created_at": "2025-06-15T10:30:00.000000Z",
    "updated_at": "2025-06-15T16:15:00.000000Z",
    "location": {
      "id": 2,
      "name": "Sidebar"
    },
    "user": {
      "id": 1,
      "name": "Admin User"
    }
  }
}
```

#### Delete Banner (Protected)

**Endpoint:** `DELETE /api/v1/banners/{id}`

**Authentication:** Required (JWT)

**Permissions:** User must be authenticated and either be the creator of the banner or have admin role

**Response:**
```json
{
  "status": "success",
  "message": "Banner deleted successfully"
}
```

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
