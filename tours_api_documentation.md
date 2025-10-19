### Tours

#### List All Tours

**Endpoint:** `GET /api/v1/tours`

**Description:** Retrieves a paginated list of all active tours with comprehensive filtering options.

**Filtering Parameters:**
- `location_id` (optional): Filter by tour location ID
- `min_price` (optional): Filter by minimum price
- `max_price` (optional): Filter by maximum price
- `date` (optional): Filter tours available on a specific calendar date (YYYY-MM-DD). Applies both weekday availability and within any defined date ranges (if any).
- `is_featured` (optional): Filter featured tours. Accepts 1/0, true/false. Alias: `featured`.
- `sort` (optional): Sort by a specific criteria. Options:
  - `price_low_high`: Price low to high
  - `price_high_low`: Price high to low
  - `newest`: Recently added
  - `rating`: Highest rated
  Default ordering (when `sort` is not provided) is by `display_order` ascending.
- `per_page` (optional): Number of items per page (default: 15)
- `page` (optional): Page number (default: 1)

**Example Requests:**
```
/api/v1/tours?location_id=1
/api/v1/tours?min_price=50&max_price=200
/api/v1/tours?sort=price_low_high&location_id=2
/api/v1/tours?date=2025-03-15
/api/v1/tours?is_featured=1
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
      "is_featured": false,
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
      "date_ranges": [
        {"id": 10, "start_date": "2025-01-01", "end_date": "2025-02-15"},
        {"id": 11, "start_date": "2025-03-10", "end_date": "2025-11-12"}
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
    "is_featured": true,
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
    "date_ranges": [
      {"id": 10, "start_date": "2025-01-01", "end_date": "2025-02-15"},
      {"id": 11, "start_date": "2025-03-10", "end_date": "2025-11-12"}
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

**Endpoint:** `GET /api/v1/tours/{id}/ratings`

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
    }
  ],
  "links": { /* pagination links */ },
  "meta": { /* pagination meta */ }
}
```

#### Rate a Tour

**Endpoint:** `POST /api/v1/tours/{id}/rate`

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

<!-- Update/Delete via separate endpoints are not available; use POST /tours/{id}/rate to create or update. -->

#### Check if User Has Rated a Tour

**Endpoint:** `GET /api/v1/tours/{id}/rating`

**Authentication:** Required (JWT)

**Description:** Returns the authenticated user's rating for a tour if it exists; otherwise 404.

**Response (200):**
```json
{
  "data": {
    "id": 3,
    "user_id": 1,
    "tour_id": 1,
    "rating": 4,
    "comment": "Good tour, but started a bit late",
    "created_at": "2025-10-01T14:30:45.000000Z",
    "updated_at": "2025-10-01T14:35:20.000000Z"
  }
}
```

**Response (404):**
```json
{
  "message": "No rating found"
}
```


### Tour Bookings

#### Create a Booking

**Endpoint:** `POST /api/v1/tours/{id}/book`

**Authentication:** Optional (guests allowed)

**Description:** Creates a new booking for a tour on a specific date. Enforces tour weekday availability, optional date ranges, and capacity.

**Request Body:**
```json
{
  "selected_date": "2025-11-10",   // Required, YYYY-MM-DD, today or later
  "adults": 2,                      // Required, >= 1
  "kids": 1,                        // Optional, >= 0
  "infants": 0,                     // Optional, >= 0
  "full_name": "Jane Doe",        // Required
  "email": "jane@example.com",    // Required, valid email
  "phone": "+96170000000",        // Required
  "special_requests": "Vegetarian meal" // Optional
}
```

**Response:**
```json
{
  "data": {
    "id": 12,
    "booking_number": "TB-251110-ABC123",
    "tour_id": 1,
    "user_id": 5,                 // null for guest
    "selected_date": "2025-11-10",
    "adults": 2,
    "kids": 1,
    "infants": 0,
    "total_price": "250.00",
    "status": "pending",
    "full_name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+96170000000",
    "special_requests": "Vegetarian meal",
    "created_at": "2025-10-19T16:30:45.000000Z",
    "updated_at": "2025-10-19T16:30:45.000000Z",
    "tour": { /* TourResource when loaded */ }
  }
}
```

#### Get Booking Status

**Endpoint:** `GET /api/v1/tour-bookings/status/{bookingNumber}`

**Authentication:** Not required

**Description:** Retrieves a booking by its booking number.

**Response:** Same format as Create a Booking (single booking resource).

#### List My Bookings

**Endpoint:** `GET /api/v1/tour-bookings`

**Authentication:** Required (JWT)

**Parameters:**
- `per_page` (optional): Items per page (default: 10)
- `page` (optional): Page number (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 12,
      "booking_number": "TB-251110-ABC123",
      "tour_id": 1,
      "user_id": 5,
      "selected_date": "2025-11-10",
      "adults": 2,
      "kids": 1,
      "infants": 0,
      "total_price": "250.00",
      "status": "pending",
      "full_name": "Jane Doe",
      "email": "jane@example.com",
      "phone": "+96170000000",
      "special_requests": "Vegetarian meal",
      "created_at": "2025-10-19T16:30:45.000000Z",
      "updated_at": "2025-10-19T16:30:45.000000Z",
      "tour": { /* TourResource when loaded */ }
    }
  ],
  "links": { /* pagination links */ },
  "meta": { /* pagination meta */ }
}
```

#### Get a Booking

**Endpoint:** `GET /api/v1/tour-bookings/{id}`

**Authentication:** Required (JWT)

**Description:** Retrieves a specific booking that belongs to the authenticated user.

**Response:** Same format as Create a Booking (single booking resource).

#### Cancel a Booking

**Endpoint:** `PATCH /api/v1/tour-bookings/{id}/cancel`

**Authentication:** Required (JWT)

**Description:** Cancels a future booking that belongs to the authenticated user.

**Response:**
```json
{
  "data": {
    "id": 12,
    "status": "canceled",
    "booking_number": "TB-251110-ABC123",
    "selected_date": "2025-11-10",
    /* other fields as above */
  }
}
```

#### Validation & Rules

- **Date rules:** `selected_date` must be today or later; must match an available weekday of the tour (`tour_day_availabilities`). If `tour_date_ranges` exist, the date must fall within at least one range.
- **Capacity:** Enforced per tour per date inside a transaction. Occupancy = `adults + kids` (infants do not consume capacity by default). If over capacity, the request is rejected.
- **Pricing:** `total_price = (adults * price) + (kids * (kids_price or price)) + (infants * (infant_price or 0))`.
- **Status:** New bookings are created with `pending`. Users can cancel future bookings, which sets status to `canceled`.

### Tour Booking Payments

#### Create Payment Link (Whish)

**Endpoint:** `POST /api/v1/tour-bookings/{bookingNumber}/payment-link`

**Authentication:** Not required

**Description:** Creates a Whish payment link for a booking identified by `bookingNumber`. Returns a URL where the user completes payment.

**Request Body:**
```json
{
  "currency": "USD",                 // Optional: LBP | USD | AED (default: config)
  "success_callback_url": "https://api.example.com/api/whish/callback/success", // Optional
  "failure_callback_url": "https://api.example.com/api/whish/callback/failure", // Optional
  "success_redirect_url": "https://app.example.com/payment/success",            // Optional
  "failure_redirect_url": "https://app.example.com/payment/failure"             // Optional
}
```

**Response:**
```json
{
  "whish_url": "https://whish.money/pay/8nQS2mL"
}
```

#### Get Payment Status (Whish)

**Endpoint:** `GET /api/v1/tour-bookings/{id}/payment-status`

**Authentication:** Required (JWT)

**Description:** Polls Whish collect status for this booking (booking id is used as `externalId`). If Whish returns `success`, the booking is updated to `confirmed`. This is primarily a fallback; server-to-server callbacks are the authoritative status updates.

**Query Parameters:**
- `currency` (optional): `LBP` | `USD` | `AED` (defaults to config)

**Response:**
```json
{
  "collect_status": "pending",   // success | failed | pending
  "booking_status": "pending"    // pending | confirmed | canceled
}
```

#### Configuration

- **Environment variables:**
  - `WHISH_CHANNEL`, `WHISH_SECRET`, `WHISH_ENV` (`testing|live`), `WHISH_WEBSITE_URL`
  - Optional defaults: `WHISH_DEFAULT_CURRENCY`, callback and redirect URLs
- **Notes:**
  - Payment `externalId` is the booking id.
  - If callback/redirect URLs are not provided in the request, backend defaults from configuration are used.
  - Use the returned `whish_url` to redirect the user to complete payment.
  - Server-to-server callbacks (`/api/whish/callback/success` and `/api/whish/callback/failure`) update booking status authoritatively; redirects are for UX only.
