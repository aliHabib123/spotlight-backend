<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

class WeatherKitService
{
    private string $privateKey;
    private string $teamId;
    private string $serviceId;
    private string $keyId;

    public function __construct()
    {
        $this->teamId = (string) config('services.weatherkit.team_id');
        $this->serviceId = (string) config('services.weatherkit.service_id');
        $this->keyId = (string) config('services.weatherkit.key_id');

        if ($this->teamId === '' || $this->serviceId === '' || $this->keyId === '') {
            throw new \RuntimeException('WeatherKit configuration is missing. Please set WEATHERKIT_TEAM_ID, WEATHERKIT_SERVICE_ID, and WEATHERKIT_KEY_ID.');
        }

        $keyPath = (string) (config('services.weatherkit.key_path') ?: '');
        if ($keyPath === '') {
            $keyPath = storage_path('app/keys/WeatherKit.p8');
        } else {
            if (!str_starts_with($keyPath, DIRECTORY_SEPARATOR)) {
                $candidate = base_path($keyPath);
                if (file_exists($candidate)) {
                    $keyPath = $candidate;
                }
            }
        }

        if (!is_file($keyPath) || !is_readable($keyPath)) {
            throw new \RuntimeException('WeatherKit private key not found or unreadable at: ' . $keyPath);
        }

        $pem = (string) file_get_contents($keyPath);
        if ($pem === '') {
            throw new \RuntimeException('WeatherKit private key file is empty: ' . $keyPath);
        }
        $this->privateKey = $pem;
    }

    private function generateJwt(): string
    {
        $now = time();
        $payload = [
            'iss' => $this->teamId,
            'sub' => $this->serviceId,
            'aud' => 'https://weatherkit.apple.com',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        return JWT::encode($payload, $this->privateKey, 'ES256', $this->keyId);
    }

    public function getWeather($lat, $lon, string $lang = 'en', string $dataSets = 'currentWeather,forecastDaily')
    {
        $token = $this->generateJwt();
        $url = "https://weatherkit.apple.com/api/v1/weather/{$lang}/{$lat}/{$lon}";
        $response = Http::withToken($token)->get($url, [
            'dataSets' => $dataSets,
        ]);
        if ($response->failed()) {
            throw new \RuntimeException('WeatherKit API Error: ' . $response->body());
        }
        return $response->json();
    }
}
