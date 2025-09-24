# Events API Documentation

## Overview
The Events API provides endpoints for managing and retrieving events, event categories, and event locations. Each event can have multiple schedules and belongs to a category and location.

## Base URL
```
/api/v1
```

## Authentication
Most endpoints are public and don't require authentication. Write operations (if implemented) would require authentication.

---

## Event Categories

### List All Event Categories
**GET** `/event-categories`

Returns a paginated list of active event categories with their event counts.

**Parameters:**
- `per_page` (optional, integer, max: 50) - Number of items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Concerts",
      "slug": "concerts",
      "description": "Music concerts and live performances",
      "is_active": true,
      "created_at": "2025-09-24T18:41:56.000000Z",
      "updated_at": "2025-09-24T18:41:56.000000Z",
      "events_count": 0
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 2,
    "last_page": 1
  }
}
```

### Get All Event Categories (Simple List)
**GET** `/event-categories/all`

Returns all active event categories without pagination.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Concerts",
      "slug": "concerts",
      "description": "Music concerts and live performances",
      "is_active": true,
      "created_at": "2025-09-24T18:41:56.000000Z",
      "updated_at": "2025-09-24T18:41:56.000000Z",
      "events_count": 0
    }
  ]
}
```

### Get Event Category Details
**GET** `/event-categories/{id}`

Returns details of a specific event category.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "name": "Concerts",
    "slug": "concerts",
    "description": "Music concerts and live performances",
    "is_active": true,
    "created_at": "2025-09-24T18:41:56.000000Z",
    "updated_at": "2025-09-24T18:41:56.000000Z",
    "events_count": 0
  }
}
```

---

## Event Locations

### List All Event Locations
**GET** `/event-locations`

Returns a paginated list of active event locations with their event counts.

**Parameters:**
- `per_page` (optional, integer, max: 50) - Number of items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Beirut Souks",
      "slug": "beirut-souks",
      "created_at": "2025-09-24T19:13:32.000000Z",
      "updated_at": "2025-09-24T19:13:32.000000Z",
      "events_count": 1
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 2,
    "last_page": 1
  }
}
```

### Get All Event Locations (Simple List)
**GET** `/event-locations/all`

Returns all active event locations without pagination.

### Get Event Location Details
**GET** `/event-locations/{id}`

Returns details of a specific event location.

### Get Events by Location
**GET** `/event-locations/{id}/events`

Returns all events at a specific location.

**Parameters:**
- `per_page` (optional, integer, max: 50) - Number of items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": [
    // Event objects (see Events section)
  ],
  "location": {
    "id": 1,
    "name": "Beirut Souks",
    "slug": "beirut-souks",
    // ... location details
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

## Events

### List All Events
**GET** `/events`

Returns a paginated list of published events with their categories, locations, and schedules.

**Parameters:**
- `per_page` (optional, integer, max: 50) - Number of items per page (default: 15)
- `category_id` (optional, integer) - Filter by event category ID
- `featured` (optional, boolean) - Filter featured events only
- `upcoming` (optional, boolean) - Filter events with future schedules only

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Summer Music Festival 2025",
      "slug": "summer-music-festival-2025",
      "description": "Join us for an amazing weekend of live music featuring local and international artists.",
      "image": null,
      "event_category_id": 2,
      "event_location_id": 1,
      "map_url": "https://maps.google.com/?q=Beirut+Souks",
      "phone_number": "+961 1 234567",
      "is_featured": true,
      "is_published": true,
      "created_at": "2025-09-24T18:50:47.000000Z",
      "updated_at": "2025-09-24T19:15:45.000000Z",
      "event_category": {
        "id": 2,
        "name": "Festivals",
        "slug": "festivals",
        "description": "Cultural and seasonal festivals",
        "is_active": true,
        "created_at": "2025-09-24T18:47:35.000000Z",
        "updated_at": "2025-09-24T18:47:35.000000Z"
      },
      "event_location": {
        "id": 1,
        "name": "Beirut Souks",
        "slug": "beirut-souks",
        "created_at": "2025-09-24T19:13:32.000000Z",
        "updated_at": "2025-09-24T19:13:32.000000Z"
      },
      "schedules": [
        {
          "id": 1,
          "event_id": 1,
          "date": "2025-10-04T00:00:00.000000Z",
          "start_time": "16:00",
          "end_time": "23:59",
          "created_at": "2025-09-24T18:51:48.000000Z",
          "updated_at": "2025-09-24T18:51:48.000000Z"
        },
        {
          "id": 2,
          "event_id": 1,
          "date": "2025-10-05T00:00:00.000000Z",
          "start_time": "10:00",
          "end_time": "22:00",
          "created_at": "2025-09-24T18:51:48.000000Z",
          "updated_at": "2025-09-24T18:51:48.000000Z"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

### Get Event Details
**GET** `/events/{id}`

Returns details of a specific event with all related data.

**Response:**
```json
{
  "status": "success",
  "data": {
    // Same structure as individual event in list above
  }
}
```

### Get Featured Events
**GET** `/events/featured`

Returns featured events only.

**Parameters:**
- `limit` (optional, integer, max: 20) - Number of events to return (default: 5)

### Get Upcoming Events
**GET** `/events/upcoming`

Returns events that have future schedules.

**Parameters:**
- `limit` (optional, integer, max: 50) - Number of events to return (default: 10)

### Get Events by Category
**GET** `/events/category/{category_id}`

Returns events filtered by category.

**Parameters:**
- `per_page` (optional, integer, max: 50) - Number of items per page (default: 15)

**Response:**
```json
{
  "status": "success",
  "data": [
    // Event objects
  ],
  "category": {
    "id": 2,
    "name": "Festivals",
    "slug": "festivals",
    "description": "Cultural and seasonal festivals",
    "is_active": true,
    "created_at": "2025-09-24T18:47:35.000000Z",
    "updated_at": "2025-09-24T18:47:35.000000Z"
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

## Data Models

### Event
- `id` (integer) - Unique event identifier
- `title` (string) - Event title
- `slug` (string) - URL-friendly event identifier
- `description` (string) - Event description (can contain HTML from rich editor)
- `image` (string|null) - Path to event image
- `event_category_id` (integer) - Related category ID
- `event_location_id` (integer|null) - Related location ID
- `map_url` (string|null) - Map URL for the event location
- `phone_number` (string) - Contact phone number (returns empty string if null)
- `is_featured` (boolean) - Whether event is featured
- `is_published` (boolean) - Whether event is publicly visible
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Event Schedule
- `id` (integer) - Unique schedule identifier
- `event_id` (integer) - Related event ID
- `date` (date) - Event date (YYYY-MM-DD)
- `start_time` (time) - Start time (HH:MM format)
- `end_time` (time) - End time (HH:MM format)
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Event Category
- `id` (integer) - Unique category identifier
- `name` (string) - Category name
- `slug` (string) - URL-friendly category identifier
- `description` (string|null) - Category description
- `is_active` (boolean) - Whether category is active
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Event Location
- `id` (integer) - Unique location identifier
- `name` (string) - Location name
- `slug` (string) - URL-friendly location identifier
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

---

## Error Responses

### 404 Not Found
When a requested resource doesn't exist:
```json
{
  "message": "No query results for model [App\\Models\\Event] 123"
}
```

### 422 Validation Error
When request parameters are invalid:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["The per page may not be greater than 50."]
  }
}
```

---

## Usage Examples

### Get all upcoming festivals
```bash
curl "https://api.example.com/v1/events?upcoming=1&category_id=2"
```

### Get featured events (limit 10)
```bash
curl "https://api.example.com/v1/events/featured?limit=10"
```

### Get events at Beirut Souks
```bash
curl "https://api.example.com/v1/event-locations/1/events"
```

### Get event details
```bash
curl "https://api.example.com/v1/events/1"
```

---

## Notes

1. **Multiple Schedules**: Events support multiple date/time combinations. For example, a festival might run "Saturday Oct 04 2025 from 04:00 PM to 12:00 AM" and "Sunday Oct 05 2025 from 10:00 AM to 10:00 PM".

2. **Relationships**: All event endpoints include related data (category, location, schedules) by default to minimize API calls.

3. **Filtering**: Events can be filtered by category, featured status, and whether they have upcoming schedules.

4. **Pagination**: Most list endpoints support pagination with customizable page size (max 50 items).

5. **Status Fields**: Events have both `is_featured` and `is_published` flags for different types of visibility control.

6. **Location Details**: Event locations include full address information and optional geographic coordinates for mapping.
