# Shop and Product API Documentation

This document provides information about the shop categories, shops, and products API endpoints.

## Shop Categories

### GET /api/v1/shop-categories
Returns a list of all shop categories.

**Query Parameters:**
- `include_inactive` (boolean): Include inactive categories
- `parent_id` (integer): Filter by parent category
- `top_level` (boolean): Only return top-level categories
- `hierarchical` (boolean): Return in hierarchical structure

### GET /api/v1/shop-categories/{id}
Returns details for a specific shop category. The `id` can be numeric ID or slug.

### GET /api/v1/shop-categories/{id}/shops
Returns shops that belong to a specific category. The `id` can be numeric ID or slug.

**Query Parameters:**
- `per_page` (integer): Number of items per page (default: 10)

## Shops

### GET /api/v1/shops
Returns a list of all shops with optional filtering.

**Query Parameters:**
- `include_inactive` (boolean): Include inactive shops
- `is_featured` (boolean): Filter by featured status
- `category_id` (integer): Filter by category ID
- `search` (string): Search by name
- `per_page` (integer): Number of items per page (default: 10)

### GET /api/v1/shops/{id}
Returns details for a specific shop. The `id` can be numeric ID or slug.

### GET /api/v1/shops/{id}/products
Returns products from a specific shop. The `id` can be numeric ID or slug.

**Query Parameters:**
- `include_inactive` (boolean): Include inactive products
- `is_featured` (boolean): Filter by featured status
- `type` (string): Filter by product type ('single' or 'variable')
- `min_price` (numeric): Filter by minimum price
- `max_price` (numeric): Filter by maximum price
- `search` (string): Search by name
- `per_page` (integer): Number of items per page (default: 12)

## Products

### GET /api/v1/products
Returns a list of all products with optional filtering.

**Query Parameters:**
- `include_inactive` (boolean): Include inactive products
- `is_featured` (boolean): Filter by featured status
- `shop_id` (integer): Filter by shop ID
- `category_id` (integer): Filter by shop category ID
- `type` (string): Filter by product type ('single' or 'variable')
- `min_price` (numeric): Filter by minimum price
- `max_price` (numeric): Filter by maximum price
- `on_sale` (boolean): Filter by on sale status
- `search` (string): Search by name or description
- `per_page` (integer): Number of items per page (default: 12)

### GET /api/v1/products/{id}
Returns details for a specific product. The `id` can be numeric ID or slug.

### GET /api/v1/products/by-category/{id}
Returns products that belong to shops in a specific category. The `id` can be numeric ID or slug.

**Query Parameters:**
- Same as the GET /api/v1/products endpoint
