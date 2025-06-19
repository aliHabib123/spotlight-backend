# E-Commerce API Documentation

This document provides detailed information about the e-commerce API endpoints for cart management, order processing, and shipping address management.

## Table of Contents

1. [Authentication](#authentication)
2. [Cart Management](#cart-management)
3. [Shipping Address Management](#shipping-address-management)
4. [Order Management](#order-management)
5. [Error Handling](#error-handling)

## Authentication

### For Guest Users
Guest users must include a session token in the request header to identify their cart:

```
X-Cart-Session: {session-token}
```

If no session token is provided when interacting with cart endpoints, a new one will be generated and returned in the response.

### For Authenticated Users
Authenticated users should include their JWT token in the Authorization header:

```
Authorization: Bearer {jwt-token}
```

## Cart Management

### Get Cart
Retrieves the current user's cart or creates a new one.

- **URL**: `/api/v1/cart`
- **Method**: `GET`
- **Authentication**: Optional
- **Headers**:
  - For guests: `X-Cart-Session: {session-token}` (optional, will be generated if not provided)
  - For authenticated users: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "data": {
    "cart": {
      "id": 1,
      "user_id": 5,
      "session_id": null,
      "created_at": "2025-06-19T15:30:45.000000Z",
      "updated_at": "2025-06-19T15:30:45.000000Z",
      "items": [
        {
          "id": 1,
          "cart_id": 1,
          "product_id": 10,
          "product_variation_id": 15,
          "quantity": 2,
          "price": 29.99,
          "attributes": { "color": "blue", "size": "M" },
          "created_at": "2025-06-19T15:30:45.000000Z",
          "updated_at": "2025-06-19T15:30:45.000000Z",
          "product": { /* product details */ },
          "variation": { /* variation details */ }
        }
      ]
    },
    "total_items": 2,
    "total": 59.98
  }
}
```

For guest users, the response will also include a `session_id` field that should be stored and sent in subsequent requests.

### Add Item to Cart
Adds a product to the cart.

- **URL**: `/api/v1/cart/items`
- **Method**: `POST`
- **Authentication**: Optional
- **Headers**:
  - For guests: `X-Cart-Session: {session-token}` (optional)
  - For authenticated users: `Authorization: Bearer {jwt-token}`
- **Body**:
```json
{
  "product_id": 10,
  "product_variation_id": 15, // Optional, required for variable products
  "quantity": 2,
  "attributes": { "color": "blue", "size": "M" } // Optional
}
```

#### Success Response
```json
{
  "status": "success",
  "message": "Item added to cart",
  "data": {
    "cart": { /* cart with items */ },
    "total_items": 2,
    "total": 59.98
  },
  "session_id": "550e8400-e29b-41d4-a716-446655440000" // Only for guest users
}
```

#### Error Responses
- **422 Validation Failed**:
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": { /* validation errors */ }
}
```
- **400 Product Not Available**:
```json
{
  "status": "error",
  "message": "Product is not available"
}
```
- **400 Not Enough Stock**:
```json
{
  "status": "error",
  "message": "Not enough stock available",
  "available_stock": 1
}
```

### Update Cart Item
Updates the quantity of an item in the cart.

- **URL**: `/api/v1/cart/items/{itemId}`
- **Method**: `PUT`
- **Authentication**: Optional
- **Headers**:
  - For guests: `X-Cart-Session: {session-token}`
  - For authenticated users: `Authorization: Bearer {jwt-token}`
- **Body**:
```json
{
  "quantity": 3
}
```

#### Success Response
```json
{
  "status": "success",
  "message": "Cart item updated",
  "data": {
    "cart": { /* cart with items */ },
    "total_items": 3,
    "total": 89.97
  }
}
```

### Remove Cart Item
Removes an item from the cart.

- **URL**: `/api/v1/cart/items/{itemId}`
- **Method**: `DELETE`
- **Authentication**: Optional
- **Headers**:
  - For guests: `X-Cart-Session: {session-token}`
  - For authenticated users: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "message": "Item removed from cart",
  "data": {
    "cart": { /* cart with remaining items */ },
    "total_items": 0,
    "total": 0
  }
}
```

### Clear Cart
Removes all items from the cart.

- **URL**: `/api/v1/cart`
- **Method**: `DELETE`
- **Authentication**: Optional
- **Headers**:
  - For guests: `X-Cart-Session: {session-token}`
  - For authenticated users: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "message": "Cart cleared",
  "data": {
    "cart": { /* empty cart */ },
    "total_items": 0,
    "total": 0
  }
}
```

## Shipping Address Management

All shipping address endpoints require authentication.

### List Shipping Addresses
Retrieves all shipping addresses for the authenticated user.

- **URL**: `/api/v1/shipping-addresses`
- **Method**: `GET`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "user_id": 5,
      "first_name": "John",
      "last_name": "Doe",
      "address_line_1": "123 Main St",
      "address_line_2": "Apt 4B",
      "city": "New York",
      "state_province": "NY",
      "postal_code": "10001",
      "country": "United States",
      "phone_number": "+1234567890",
      "is_default": true,
      "delivery_instructions": "Leave at the door",
      "created_at": "2025-06-19T15:30:45.000000Z",
      "updated_at": "2025-06-19T15:30:45.000000Z"
    }
  ]
}
```

### Create Shipping Address
Creates a new shipping address for the authenticated user.

- **URL**: `/api/v1/shipping-addresses`
- **Method**: `POST`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`
- **Body**:
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "address_line_1": "123 Main St",
  "address_line_2": "Apt 4B", // Optional
  "city": "New York",
  "state_province": "NY", // Optional
  "postal_code": "10001", // Optional
  "country": "United States",
  "phone_number": "+1234567890",
  "is_default": true, // Optional, defaults to false
  "delivery_instructions": "Leave at the door" // Optional
}
```

#### Success Response
```json
{
  "status": "success",
  "message": "Shipping address created successfully",
  "data": { /* shipping address details */ }
}
```

### Get Shipping Address
Retrieves a specific shipping address.

- **URL**: `/api/v1/shipping-addresses/{id}`
- **Method**: `GET`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "data": { /* shipping address details */ }
}
```

### Update Shipping Address
Updates a shipping address.

- **URL**: `/api/v1/shipping-addresses/{id}`
- **Method**: `PUT`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`
- **Body**: Any of the fields from the create endpoint

#### Success Response
```json
{
  "status": "success",
  "message": "Shipping address updated successfully",
  "data": { /* updated shipping address */ }
}
```

### Set Default Shipping Address
Sets a shipping address as the default.

- **URL**: `/api/v1/shipping-addresses/{id}/set-default`
- **Method**: `PATCH`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "message": "Default shipping address updated",
  "data": { /* shipping address details */ }
}
```

### Delete Shipping Address
Deletes a shipping address.

- **URL**: `/api/v1/shipping-addresses/{id}`
- **Method**: `DELETE`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "message": "Shipping address deleted successfully"
}
```

## Order Management

### Create Order
Creates a new order from the current cart.

- **URL**: `/api/v1/orders`
- **Method**: `POST`
- **Authentication**: Optional (but shipping address must exist)
- **Headers**:
  - For guests: `X-Cart-Session: {session-token}`
  - For authenticated users: `Authorization: Bearer {jwt-token}`
- **Body**:
```json
{
  "shipping_address_id": 1,
  "payment_method": "credit_card",
  "shipping_cost": 5.99, // Optional
  "tax": 2.50, // Optional
  "notes": "Please deliver in the morning" // Optional
}
```

#### Success Response
```json
{
  "status": "success",
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-20250619-ABCD",
      "user_id": 5,
      "shipping_address_id": 1,
      "subtotal": 59.98,
      "tax": 2.50,
      "shipping_cost": 5.99,
      "total": 68.47,
      "payment_method": "credit_card",
      "payment_status": "pending",
      "order_status": "pending",
      "notes": "Please deliver in the morning",
      "created_at": "2025-06-19T15:30:45.000000Z",
      "updated_at": "2025-06-19T15:30:45.000000Z",
      "items": [ /* order items */ ],
      "shipping_address": { /* shipping address details */ }
    },
    "order_number": "ORD-20250619-ABCD"
  }
}
```

### List Orders (Authenticated Users Only)
Retrieves all orders for the authenticated user.

- **URL**: `/api/v1/orders`
- **Method**: `GET`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20250619-ABCD",
      /* other order fields */
      "items": [ /* order items */ ],
      "shipping_address": { /* shipping address details */ }
    }
  ]
}
```

### Get Order Details
Retrieves details for a specific order.

- **URL**: `/api/v1/orders/{id}` (for authenticated users)
- **URL**: `/api/v1/orders/{order_number}` (for guest users)
- **Method**: `GET`
- **Authentication**: Required for user-specific orders

#### Success Response
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "order_number": "ORD-20250619-ABCD",
    /* other order fields */
    "items": [ /* order items */ ],
    "shipping_address": { /* shipping address details */ }
  }
}
```

### Check Order Status
Checks the status of an order by its order number.

- **URL**: `/api/v1/orders/status/{orderNumber}`
- **Method**: `GET`
- **Authentication**: Not required

#### Success Response
```json
{
  "status": "success",
  "data": {
    "order_number": "ORD-20250619-ABCD",
    "order_status": "shipped",
    "payment_status": "paid",
    "created_at": "2025-06-19T15:30:45.000000Z",
    "paid_at": "2025-06-19T15:35:22.000000Z",
    "shipped_at": "2025-06-20T09:15:33.000000Z",
    "delivered_at": null
  }
}
```

### Cancel Order
Cancels a pending order.

- **URL**: `/api/v1/orders/{id}/cancel`
- **Method**: `PATCH`
- **Authentication**: Required
- **Headers**: `Authorization: Bearer {jwt-token}`

#### Success Response
```json
{
  "status": "success",
  "message": "Order cancelled successfully",
  "data": { /* order details */ }
}
```

#### Error Response
```json
{
  "status": "error",
  "message": "Order cannot be cancelled at this stage"
}
```

## Error Handling

### Common Error Responses

#### 401 Unauthorized
```json
{
  "status": "error",
  "message": "Unauthenticated"
}
```

#### 404 Not Found
```json
{
  "status": "error",
  "message": "Resource not found"
}
```

#### 422 Validation Error
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "field_name": [
      "The field_name field is required."
    ]
  }
}
```

#### 500 Server Error
```json
{
  "status": "error",
  "message": "An unexpected error occurred",
  "error": "Error details" // Only in development
}
```

## Cart Session Management

### For Frontend Developers

1. When a guest user first interacts with the cart endpoints, store the returned `session_id` in local storage or cookies.
2. Include this `session_id` in the `X-Cart-Session` header for all subsequent cart-related requests.
3. When a user logs in, their session cart will automatically be merged with their user cart if both exist.

### For Mobile App Developers

1. Store the `session_id` securely in the app's storage.
2. Include the `session_id` in all cart-related API requests.
3. After user authentication, continue using the same cart endpoints but with the JWT token instead of the session ID.
