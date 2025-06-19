<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AdLocationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BannerLocationController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NewsCategoryController;
use App\Http\Controllers\Api\SpotlightController;
use App\Http\Controllers\Api\SpotlightCategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\LocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// JWT Authentication routes for mobile app
Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Protected JWT routes
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Public API routes
Route::prefix('v1')->group(function () {
    // News Categories
    Route::get('/news/categories', [NewsCategoryController::class, 'index']);
    Route::get('/news/categories/{id}', [NewsCategoryController::class, 'show']);

    // News
    Route::get('/news/latest', [NewsController::class, 'latest']);
    Route::get('/news/featured', [NewsController::class, 'featured']);
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{id}', [NewsController::class, 'show']);
    // Spotlight Categories
    Route::get('/categories', [SpotlightCategoryController::class, 'index']);
    Route::get('/categories/location/{locationSlug}', [SpotlightCategoryController::class, 'byLocation']);
    Route::get('/categories/{category}', [SpotlightCategoryController::class, 'show']);
    Route::get('/categories/{category}/attributes', [SpotlightCategoryController::class, 'attributes']);
    Route::get('/categories/{category}/filters', [SpotlightCategoryController::class, 'filters']);

    // Spotlights
    Route::get('/spotlights', [SpotlightController::class, 'index']);
    Route::get('/spotlights/featured', [SpotlightController::class, 'featured']);
    Route::get('/spotlights/trending', [SpotlightController::class, 'trending']);
    Route::get('/spotlights/{spotlight}', [SpotlightController::class, 'show']);
    Route::get('/spotlights/category/{category}', [SpotlightController::class, 'byCategory']);
    Route::get('/spotlights/{spotlight}/attributes', [SpotlightController::class, 'attributes']);

    // Tags
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/tags/{tag}/spotlights', [TagController::class, 'spotlights']);

    // Locations
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{location}/spotlights', [LocationController::class, 'spotlights']);

    // Attribute Definitions - Read only for public
    Route::get('/attributes', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'index']);
    Route::get('/attributes/{attribute}', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'show']);

    // Banner Locations - Public
    Route::get('/banners/locations', [BannerLocationController::class, 'index']);
    Route::get('/banners/locations/{id}', [BannerLocationController::class, 'show']);

    // Banners - Public
    Route::get('/banners', [BannerController::class, 'index']);
    Route::get('/banners/{id}', [BannerController::class, 'show']);
    Route::get('/banners/by-location/{slug}', [BannerController::class, 'byLocation']);

    // Ads - Public
    Route::get('/ads', [AdController::class, 'index']);
    Route::get('/ads/{id}', [AdController::class, 'show']);
    Route::get('/ads/by-location/{locationId}', [AdController::class, 'getAdsByLocation']);

    // Ad Locations - Public
    Route::get('/ads/locations', [AdLocationController::class, 'index']);
    Route::get('/ads/locations/{id}', [AdLocationController::class, 'show']);
});

// Protected API routes
Route::middleware(['auth:api'])->prefix('v1')->group(function () {
    // News management
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{id}', [NewsController::class, 'update']);
    Route::delete('/news/{id}', [NewsController::class, 'destroy']);

    // News categories management
    Route::post('/news/categories', [NewsCategoryController::class, 'store']);
    Route::put('/news/categories/{id}', [NewsCategoryController::class, 'update']);
    Route::delete('/news/categories/{id}', [NewsCategoryController::class, 'destroy']);

    // Banner management
    Route::post('/banners', [BannerController::class, 'store']);
    Route::put('/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);

    // Banner locations management
    Route::post('/banners/locations', [BannerLocationController::class, 'store']);
    Route::put('/banners/locations/{id}', [BannerLocationController::class, 'update']);
    Route::delete('/banners/locations/{id}', [BannerLocationController::class, 'destroy']);

    // Ad management
    Route::post('/ads', [AdController::class, 'store']);
    Route::put('/ads/{id}', [AdController::class, 'update']);
    Route::delete('/ads/{id}', [AdController::class, 'destroy']);

    // Ad locations management
    Route::post('/ads/locations', [AdLocationController::class, 'store']);
    Route::put('/ads/locations/{id}', [AdLocationController::class, 'update']);
    Route::delete('/ads/locations/{id}', [AdLocationController::class, 'destroy']);
});

// Protected API routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Spotlight management
    Route::post('/spotlights', [SpotlightController::class, 'store']);
    Route::put('/spotlights/{spotlight}', [SpotlightController::class, 'update']);
    Route::delete('/spotlights/{spotlight}', [SpotlightController::class, 'destroy']);
    Route::patch('/spotlights/{spotlight}/publish', [SpotlightController::class, 'publish']);
    Route::patch('/spotlights/{spotlight}/unpublish', [SpotlightController::class, 'unpublish']);
    Route::patch('/spotlights/{spotlight}/feature', [SpotlightController::class, 'feature']);
    Route::patch('/spotlights/{spotlight}/unfeature', [SpotlightController::class, 'unfeature']);

    // Spotlight attribute values
    Route::post('/spotlights/{spotlight}/attributes', [SpotlightController::class, 'storeAttributes']);
    Route::put('/spotlights/{spotlight}/attributes/{attributeValue}', [SpotlightController::class, 'updateAttribute']);
    Route::delete('/spotlights/{spotlight}/attributes/{attributeValue}', [SpotlightController::class, 'deleteAttribute']);

    // Categories management (admin only)
    Route::middleware(['can:manage categories'])->group(function () {
        Route::post('/categories', [SpotlightCategoryController::class, 'store']);
        Route::put('/categories/{category}', [SpotlightCategoryController::class, 'update']);
        Route::delete('/categories/{category}', [SpotlightCategoryController::class, 'destroy']);

        // Category attribute management
        Route::post('/categories/{category}/attributes', [SpotlightCategoryController::class, 'attachAttributes']);
        Route::delete('/categories/{category}/attributes/{attribute}', [SpotlightCategoryController::class, 'detachAttribute']);
        Route::put('/categories/{category}/attributes/{attribute}/order', [SpotlightCategoryController::class, 'updateAttributeOrder']);
    });

    // Tags management (admin only)
    Route::middleware(['can:manage tags'])->group(function () {
        Route::post('/tags', [TagController::class, 'store']);
        Route::put('/tags/{tag}', [TagController::class, 'update']);
        Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
    });

    // Location management (admin only)
    Route::middleware(['can:manage locations'])->group(function () {
        Route::post('/locations', [LocationController::class, 'store']);
        Route::put('/locations/{location}', [LocationController::class, 'update']);
        Route::delete('/locations/{location}', [LocationController::class, 'destroy']);
    });

    // Attribute definitions management (admin only)
    Route::middleware(['can:manage attributes'])->group(function () {
        Route::post('/attributes', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'store']);
        Route::put('/attributes/{attribute}', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'update']);
        Route::delete('/attributes/{attribute}', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'destroy']);

        // Attribute options management
        Route::post('/attributes/{attribute}/options', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'storeOption']);
        Route::put('/attributes/options/{option}', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'updateOption']);
        Route::delete('/attributes/options/{option}', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'deleteOption']);
    });
});
