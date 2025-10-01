### Tours

#### List All Tours

**Endpoint:** `GET /api/v1/tours`

**Description:** Retrieves a paginated list of all active tours with comprehensive filtering options.

**Filtering Parameters:**
- `location_id` (optional): Filter by tour location ID
- `min_price` (optional): Filter by minimum price
- `max_price` (optional): Filter by maximum price
- `sort` (optional): Sort by specific criteria. Available options:
  - `price_asc`: Price low to high
  - `price_desc`: Price high to low
  - `newest`: Recently added (default)
  - `rating`: Highest rated
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Example Requests:**
```
/api/v1/tours?location_id=1
/api/v1/tours?min_price=50&max_price=200
/api/v1/tours?sort=price_asc&location_id=2
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "title": "Historic Downtown Tour",
      "slug": "historic-downtown-tour",
      "description": "<p>Explore the historic downtown area with our guided tour...</p>",
      "price": "100.00",
      "kids_price": "50.00",
      "infant_price": "0.00",
      "capacity": 20,
      "display_order": 0,
      "active": true,
      "location": {
        "id": 1,
        "name": "Downtown",
        "slug": "downtown",
        "created_at": "2025-09-30T19:18:26.000000Z",
        "updated_at": "2025-09-30T19:18:26.000000Z"
      },
      "images": [
        {
          "id": 1,
          "image_path": "tours/01K6E2WBQ8S1XW25NE7HHQEE61.png",
          "image_url": "http://127.0.0.1:8000/storage/tours/01K6E2WBQ8S1XW25NE7HHQEE61.png",
          "display_order": 0,
          "created_at": "2025-09-30T19:51:36.000000Z",
          "updated_at": "2025-09-30T19:51:36.000000Z"
        },
        {
          "id": 2,
          "image_path": "tours/01K6E2WK16VRRYWP535F2CQWGE.png",
          "image_url": "http://127.0.0.1:8000/storage/tours/01K6E2WK16VRRYWP535F2CQWGE.png",
          "display_order": 0,
          "created_at": "2025-09-30T19:51:43.000000Z",
          "updated_at": "2025-09-30T19:51:43.000000Z"
        }
      ],
      "available_days": [
        "monday",
        "wednesday",
        "friday"
      ],
      "average_rating": 4.5,
      "review_count": 12,
      "created_at": "2025-09-30T19:45:31.000000Z",
      "updated_at": "2025-09-30T20:48:57.000000Z"
    },
    // More tours...
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/tours?page=1",
    "last": "http://127.0.0.1:8000/api/v1/tours?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://127.0.0.1:8000/api/v1/tours?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "path": "http://127.0.0.1:8000/api/v1/tours",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

#### Get Tour Details

**Endpoint:** `GET /api/v1/tours/{tour}`

**Description:** Retrieves detailed information about a specific tour.

**Parameters:**
- `tour`: Tour ID or slug

**Response:**
```json
{
  "data": {
    "id": 1,
    "title": "Historic Downtown Tour",
    "slug": "historic-downtown-tour",
    "description": "<p>Explore the historic downtown area with our guided tour...</p>",
    "price": "100.00",
    "kids_price": "50.00",
    "infant_price": "0.00",
    "capacity": 20,
    "display_order": 0,
    "active": true,
    "location": {
      "id": 1,
      "name": "Downtown",
      "slug": "downtown",
      "created_at": "2025-09-30T19:18:26.000000Z",
      "updated_at": "2025-09-30T19:18:26.000000Z"
    },
    "images": [
      {
        "id": 1,
        "image_path": "tours/01K6E2WBQ8S1XW25NE7HHQEE61.png",
        "image_url": "http://127.0.0.1:8000/storage/tours/01K6E2WBQ8S1XW25NE7HHQEE61.png",
        "display_order": 0,
        "created_at": "2025-09-30T19:51:36.000000Z",
        "updated_at": "2025-09-30T19:51:36.000000Z"
      },
      {
        "id": 2,
        "image_path": "tours/01K6E2WK16VRRYWP535F2CQWGE.png",
        "image_url": "http://127.0.0.1:8000/storage/tours/01K6E2WK16VRRYWP535F2CQWGE.png",
        "display_order": 0,
        "created_at": "2025-09-30T19:51:43.000000Z",
        "updated_at": "2025-09-30T19:51:43.000000Z"
      }
    ],
    "available_days": [
      "monday",
      "wednesday",
      "friday"
    ],
    "average_rating": 4.5,
    "review_count": 12,
    "created_at": "2025-09-30T19:45:31.000000Z",
    "updated_at": "2025-09-30T20:48:57.000000Z"
  },
  "user_rating": {  // Only present if user is authenticated and has rated this tour
    "id": 3,
    "user_id": 1,
    "tour_id": 1,
    "rating": 5,
    "comment": "Amazing tour experience!",
    "created_at": "2025-10-01T09:30:45.000000Z",
    "updated_at": "2025-10-01T09:30:45.000000Z"
  }
}
```

#### Get Tour Locations

**Endpoint:** `GET /api/v1/tour-locations`

**Description:** Retrieves a list of all available tour locations.

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
      "slug": "downtown",
      "description": "Historic downtown area",
      "created_at": "2025-09-30T19:18:26.000000Z",
      "updated_at": "2025-09-30T19:18:26.000000Z"
    },
    {
      "id": 2,
      "name": "Mountain Trail",
      "slug": "mountain-trail",
      "description": "Scenic mountain hiking trails",
      "created_at": "2025-09-30T19:18:26.000000Z",
      "updated_at": "2025-09-30T19:18:26.000000Z"
    }
  ],
  "links": {
    "first": "http://127.0.0.1:8000/api/v1/tour-locations?page=1",
    "last": "http://127.0.0.1:8000/api/v1/tour-locations?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://127.0.0.1:8000/api/v1/tour-locations?page=1",
        "label": "1",
        "active": true
      },
      {
        "url": null,
        "label": "Next &raquo;",
        "active": false
      }
    ],
    "path": "http://127.0.0.1:8000/api/v1/tour-locations",
    "per_page": 15,
    "to": 2,
    "total": 2
  }
}
```

#### Tours by Location

**Endpoint:** `GET /api/v1/tour-locations/{location}/tours`

**Description:** Retrieves a paginated list of tours for a specific location.

**Parameters:**
- `location`: Location ID or slug
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Response:** Same format as List All Tours

### Tour Ratings

#### List Tour Ratings

**Endpoint:** `GET /api/v1/tour-ratings/{tour}`

**Authentication:** Optional

**Description:** Retrieves all ratings for a specific tour.

**Parameters:**
- `tour`: Tour ID or slug

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "tour_id": 1,
      "rating": 5,
      "comment": "Excellent tour!",
      "created_at": "2025-10-01T12:30:45.000000Z",
      "updated_at": "2025-10-01T12:30:45.000000Z",
      "user": {
        "id": 2,
        "name": "John Doe"
      }
    },
    {
      "id": 2,
      "user_id": 3,
      "tour_id": 1,
      "rating": 4,
      "comment": "Great tour but a bit expensive",
      "created_at": "2025-10-01T13:45:12.000000Z",
      "updated_at": "2025-10-01T13:45:12.000000Z",
      "user": {
        "id": 3,
        "name": "Jane Smith"
      }
    }
  ],
  "average_rating": 4.5,
  "review_count": 2
}
```

#### Rate a Tour

**Endpoint:** `POST /api/v1/tour-ratings/{tour}`

**Authentication:** Required (JWT)

**Description:** Creates or updates the authenticated user's rating for a tour.

**Parameters:**
- `tour`: Tour ID or slug

**Request Body:**
```json
{
  "rating": 5,           // Required, integer from 1-5
  "comment": "Amazing tour! The guide was very knowledgeable." // Optional
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Tour rated successfully",
  "data": {
    "id": 3,
    "user_id": 1,
    "tour_id": 1,
    "rating": 5,
    "comment": "Amazing tour! The guide was very knowledgeable.",
    "created_at": "2025-10-01T14:30:45.000000Z",
    "updated_at": "2025-10-01T14:30:45.000000Z"
  },
  "average_rating": 4.7,
  "review_count": 3
}
```

#### Update a Rating

**Endpoint:** `PUT /api/v1/tour-ratings/{tour}`

**Authentication:** Required (JWT)

**Description:** Updates the authenticated user's existing rating for a tour.

**Parameters:**
- `tour`: Tour ID or slug

**Request Body:**
```json
{
  "rating": 4,           // Required, integer from 1-5
  "comment": "Good tour, but started a bit late" // Optional
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Rating updated successfully",
  "data": {
    "id": 3,
    "user_id": 1,
    "tour_id": 1,
    "rating": 4,
    "comment": "Good tour, but started a bit late",
    "created_at": "2025-10-01T14:30:45.000000Z",
    "updated_at": "2025-10-01T14:35:20.000000Z"
  },
  "average_rating": 4.3,
  "review_count": 3
}
```

#### Delete a Rating

**Endpoint:** `DELETE /api/v1/tour-ratings/{tour}`

**Authentication:** Required (JWT)

**Description:** Deletes the authenticated user's rating for a tour.

**Parameters:**
- `tour`: Tour ID or slug

**Response:**
```json
{
  "status": "success",
  "message": "Rating deleted successfully"
}
```

#### Check if User Has Rated a Tour

**Endpoint:** `GET /api/v1/tour-ratings/{tour}/check`

**Authentication:** Required (JWT)

**Description:** Checks if the authenticated user has already rated a tour.

**Parameters:**
- `tour`: Tour ID or slug

**Response:**
```json
{
  "status": "success",
  "rated": true,
  "data": {
    "id": 3,
    "user_id": 1,
    "tour_id": 1,
    "rating": 4,
    "comment": "Good tour, but started a bit late",
    "created_at": "2025-10-01T14:30:45.000000Z",
    "updated_at": "2025-10-01T14:35:20.000000Z"
  },
  "average_rating": 4.3,
  "review_count": 3
}
```

If the user hasn't rated the tour:

```json
{
  "status": "success",
  "rated": false,
  "average_rating": 4.5,
  "review_count": 2
}
```

### Saved Tours

#### List Saved Tours

**Endpoint:** `GET /api/v1/saved-tours`

**Authentication:** Required (JWT)

**Description:** Retrieves a paginated list of tours saved by the authenticated user.

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
        "title": "Historic Downtown Tour",
        "slug": "historic-downtown-tour",
        "description": "<p>Explore the historic downtown area with our guided tour...</p>",
        "price": "100.00",
        "kids_price": "50.00",
        "infant_price": "0.00",
        "capacity": 20,
        "display_order": 0,
        "active": true,
        "location": {
          "id": 1,
          "name": "Downtown",
          "slug": "downtown"
        },
        "images": [
          {
            "id": 1,
            "image_path": "tours/01K6E2WBQ8S1XW25NE7HHQEE61.png",
            "image_url": "http://127.0.0.1:8000/storage/tours/01K6E2WBQ8S1XW25NE7HHQEE61.png"
          }
        ],
        "available_days": ["monday", "wednesday", "friday"],
        "average_rating": 4.5,
        "review_count": 12
      }
    ],
    "first_page_url": "http://localhost/api/v1/saved-tours?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost/api/v1/saved-tours?page=1",
    "links": [...],
    "next_page_url": null,
    "path": "http://localhost/api/v1/saved-tours",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

#### Save a Tour

**Endpoint:** `POST /api/v1/saved-tours/{tour}`

**Authentication:** Required (JWT)

**Description:** Saves a tour for the authenticated user.

**Parameters:**
- `tour`: Tour ID or slug

**Response:**
```json
{
  "status": "success",
  "message": "Tour saved successfully",
  "data": {
    "user_id": 1,
    "tour_id": 1,
    "updated_at": "2025-10-01T15:25:00.000000Z",
    "created_at": "2025-10-01T15:25:00.000000Z",
    "id": 1
  }
}
```

#### Unsave a Tour

**Endpoint:** `DELETE /api/v1/saved-tours/{tour}`

**Authentication:** Required (JWT)

**Description:** Removes a tour from the authenticated user's saved tours.

**Parameters:**
- `tour`: Tour ID or slug

**Response:**
```json
{
  "status": "success",
  "message": "Tour unsaved successfully"
}
```

#### Check if Tour is Saved

**Endpoint:** `GET /api/v1/saved-tours/{tour}/check`

**Authentication:** Required (JWT)

**Description:** Checks if a tour is saved by the authenticated user.

**Parameters:**
- `tour`: Tour ID or slug

**Response:**
```json
{
  "status": "success",
  "data": {
    "is_saved": true
  }
}
```

### Tour Bookings (Coming Soon)

**Note:** Tour booking endpoints will be added in a future API update. These will include:

- Creating tour bookings
- Managing bookings
- Processing payments
- Cancellation policies
- Retrieving booking history
