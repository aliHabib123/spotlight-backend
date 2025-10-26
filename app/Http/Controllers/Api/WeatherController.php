<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    public function show(Request $request, WeatherKitService $weatherKit)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'lang' => ['sometimes', 'string'],
            'dataSets' => ['sometimes', 'string'],
        ]);

        $lat = (string) $validated['lat'];
        $lon = (string) $validated['lon'];
        $lang = (string) ($validated['lang'] ?? 'en');
        $dataSets = (string) ($validated['dataSets'] ?? 'currentWeather,forecastDaily');

        $cacheKey = "weather_{$lat}_{$lon}_{$lang}_" . md5($dataSets);

        try {
            $data = Cache::remember($cacheKey, 3600, function () use ($lat, $lon, $lang, $dataSets, $weatherKit) {
                return $weatherKit->getWeather($lat, $lon, $lang, $dataSets);
            });
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
