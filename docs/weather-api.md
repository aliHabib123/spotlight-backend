# Weather API (Apple WeatherKit)

This document describes the public endpoint that proxies Apple WeatherKit data for your apps.

- Base URL: `/api/v1/weather`
- Controller: `App/Http/Controllers/Api/WeatherController.php`
- Service: `App/Services/WeatherKitService.php`
- Auth: Public (no authentication)

## Overview
The endpoint generates an Apple WeatherKit ES256 JWT on the server and fetches weather data from `https://weatherkit.apple.com`. Results are cached for 1 hour per unique combination of `lat`, `lon`, `lang`, and `dataSets`.

## Prerequisites
1. Place your private key (.p8) file at one of the following:
   - Recommended via env: set `WEATHERKIT_KEY_PATH` to the full or project-relative path
   - Or rely on the configured default: `storage/app/private/WeatherKit_35CD9B7MLT.p8`
2. Configure the following environment variables in `.env`:
   ```env
   WEATHERKIT_KEY_PATH=storage/app/private/WeatherKit_35CD9B7MLT.p8
   WEATHERKIT_TEAM_ID=your_team_id
   WEATHERKIT_SERVICE_ID=com.yourcompany.weatherkit
   WEATHERKIT_KEY_ID=your_key_id
   ```
3. Install dependency (already installed):
   ```bash
   composer require firebase/php-jwt
   ```

## Endpoint
GET `/api/v1/weather`

### Query Parameters
- `lat` (required, number): Latitude in decimal degrees (range: -90..90)
- `lon` (required, number): Longitude in decimal degrees (range: -180..180)
- `lang` (optional, string): Language/locale code for WeatherKit (default: `en`)
- `dataSets` (optional, string): Comma-separated WeatherKit datasets to include (default: `currentWeather,forecastDaily`)

Common dataset values supported by WeatherKit include:
- `currentWeather`
- `forecastDaily`
- `forecastHourly`
- `forecastNextHour`
- `weatherAlerts`

Example: `dataSets=currentWeather,forecastDaily,forecastHourly`

### Example Requests
- cURL (local dev):
```bash
curl "http://localhost:8000/api/v1/weather?lat=25.276987&lon=55.296249"
```

- With custom language and datasets:
```bash
curl "http://localhost:8000/api/v1/weather?lat=25.276987&lon=55.296249&lang=ar&dataSets=currentWeather,forecastDaily,forecastHourly"
```

### Success Response (example)
```json
{
  "currentWeather": {
    "name": "Current Weather",
    "metadata": { "units": "m" },
    "temperature": { "value": 34.5, "unit": "celsius" },
    "humidity": 0.45,
    "conditionCode": "Clear"
  },
  "forecastDaily": {
    "days": [
      {
        "forecastStart": "2025-10-26",
        "maxTemperature": { "value": 36, "unit": "celsius" },
        "minTemperature": { "value": 26, "unit": "celsius" }
      }
    ]
  }
}
```
Note: The exact structure is defined by Apple WeatherKit and may evolve. This API returns the response as-is from Apple.

## Caching
- The controller caches results for 3600 seconds (1 hour) per `lat`, `lon`, `lang`, and `dataSets`.
- To get fresh data sooner, change one of those parameters or clear your cache manually at the server.

## Errors
- `400 Bad Request`: Missing or invalid parameters (`lat`/`lon` out of range).
- `500 Internal Server Error`:
  - WeatherKit configuration missing (`WEATHERKIT_*` envs).
  - Private key not found/unreadable.
  - Upstream WeatherKit error (body is relayed for diagnostics).

### Error Response (example)
```json
{"error": "WeatherKit API Error: <upstream message>"}
```

## Security Notes
- The WeatherKit private key is never exposed to clients; it is used server-side to sign JWTs.
- Keep the `.p8` file out of version control and in a secure path such as `storage/app/private/`.

## Implementation References
- Service: `App/Services/WeatherKitService.php` (generates ES256 JWT and calls Apple API)
- Controller: `App/Http/Controllers/Api/WeatherController.php` (validates input and caches response)
- Config: `config/services.php` under `weatherkit` block

## Tips
- WeatherKit updates are hourly; relying on the 1-hour cache aligns with update frequency.
- You can localize output by setting the `lang` parameter (e.g., `en`, `ar`, `fr`).
- Add additional datasets via `dataSets` depending on app needs (e.g., include `forecastHourly` or `weatherAlerts`).
