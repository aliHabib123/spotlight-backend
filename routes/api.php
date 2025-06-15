<?php

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

// Public API routes
Route::prefix('v1')->group(function () {
    // Spotlight Categories
    Route::get('/categories', [SpotlightCategoryController::class, 'index']);
    Route::get('/categories/{category}', [SpotlightCategoryController::class, 'show']);
    Route::get('/categories/{category}/attributes', [SpotlightCategoryController::class, 'attributes']);
    Route::get('/categories/{category}/filters', [SpotlightCategoryController::class, 'filters']);
    
    // Spotlights
    Route::get('/spotlights', [SpotlightController::class, 'index']);
    Route::get('/spotlights/{spotlight}', [SpotlightController::class, 'show']);
    Route::get('/spotlights/featured', [SpotlightController::class, 'featured']);
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
