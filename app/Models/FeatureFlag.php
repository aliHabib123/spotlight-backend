<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'is_enabled',
        'config',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
    ];
    
    /**
     * Check if a feature is enabled.
     *
     * @param string $key
     * @return bool
     */
    public static function isEnabled(string $key): bool
    {
        return Cache::remember("feature_flag_{$key}", 3600, function () use ($key) {
            $flag = static::where('key', $key)->first();
            return $flag ? $flag->is_enabled : false;
        });
    }
    
    /**
     * Get configuration for a feature.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getConfig(string $key, $default = null): mixed
    {
        return Cache::remember("feature_config_{$key}", 3600, function () use ($key, $default) {
            $flag = static::where('key', $key)->first();
            return $flag ? $flag->config : $default;
        });
    }
    
    /**
     * Clear the cache for this feature flag.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget("feature_flag_{$this->key}");
        Cache::forget("feature_config_{$this->key}");
    }
    
    /**
     * Hook into the saved event to clear cache.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saved(function ($featureFlag) {
            $featureFlag->clearCache();
        });
        
        static::deleted(function ($featureFlag) {
            $featureFlag->clearCache();
        });
    }
}
