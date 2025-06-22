<?php

use App\Http\Controllers\Api\AdController;
use App\Http\Controllers\Api\AdLocationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AboutUsController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\BannerLocationController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NewsCategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SavedSpotlightController;
use App\Http\Controllers\Api\ShippingAddressController;
use App\Http\Controllers\Api\ShopCategoryController;
use App\Http\Controllers\Api\ShopController;
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
    // About Us
    Route::get('/about-us', [AboutUsController::class, 'get']);
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
    Route::get('/ads/by-location-slug/{slug}', [AdController::class, 'getAdsByLocationSlug']);

    // Ad Locations - Public
    Route::get('/ads/locations', [AdLocationController::class, 'index']);
    Route::get('/ads/locations/{id}', [AdLocationController::class, 'show']);
    Route::get('/ads/locations/slug/{slug}', [AdLocationController::class, 'showBySlug']);
    
    // E-commerce - Cart (accessible by both guests and authenticated users)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });
    
    // E-commerce - Orders (public access for order status and creation)
    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store']); // Create order from cart
        Route::get('/status/{orderNumber}', [OrderController::class, 'getStatus']); // Check order status by order number
    });
    
    // Contact Us form submission (public access)
    Route::post('/contact', [ContactController::class, 'submit']);
    
    // Shop Categories - Public
    Route::prefix('shop-categories')->group(function () {
        Route::get('/', [ShopCategoryController::class, 'index']);
        Route::get('/{id}', [ShopCategoryController::class, 'show']);
        Route::get('/{id}/shops', [ShopCategoryController::class, 'shops']);
    });
    
    // Shops - Public
    Route::prefix('shops')->group(function () {
        Route::get('/', [ShopController::class, 'index']);
        Route::get('/{id}', [ShopController::class, 'show']);
        Route::get('/{id}/products', [ShopController::class, 'products']);
    });
    
    // Products - Public
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::get('/by-category/{id}', [ProductController::class, 'byCategory']);
    });
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
    
    // E-commerce - Shipping Addresses (authenticated users only)
    Route::prefix('shipping-addresses')->group(function () {
        Route::get('/', [ShippingAddressController::class, 'index']);
        Route::post('/', [ShippingAddressController::class, 'store']);
        Route::get('/{id}', [ShippingAddressController::class, 'show']);
        Route::put('/{id}', [ShippingAddressController::class, 'update']);
        Route::delete('/{id}', [ShippingAddressController::class, 'destroy']);
        Route::patch('/{id}/set-default', [ShippingAddressController::class, 'setDefault']);
    });
    
    // E-commerce - Orders (authenticated users only)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']); // List user's orders
        Route::get('/{id}', [OrderController::class, 'show']); // View specific order
        Route::patch('/{id}/cancel', [OrderController::class, 'cancel']); // Cancel an order
    });
    
    // Saved Spotlights (authenticated users only)
    Route::prefix('saved-spotlights')->group(function () {
        Route::get('/', [SavedSpotlightController::class, 'index']); // List user's saved spotlights
        Route::post('/{id}', [SavedSpotlightController::class, 'save']); // Save a spotlight
        Route::delete('/{id}', [SavedSpotlightController::class, 'unsave']); // Unsave a spotlight
        Route::get('/{id}/check', [SavedSpotlightController::class, 'check']); // Check if a spotlight is saved
    });
    
    // Contact Messages Management (admin only)
    Route::middleware(['can:manage contact messages'])->prefix('contact-messages')->group(function () {
        Route::get('/', [ContactController::class, 'index']); // List all contact messages
        Route::get('/{id}', [ContactController::class, 'show']); // Show a specific message
        Route::patch('/{id}/mark-read', [ContactController::class, 'markAsRead']); // Mark message as read
        Route::delete('/{id}', [ContactController::class, 'destroy']); // Delete a message
    });
    
    // About Us Management (admin only)
    Route::middleware(['can:manage about us'])->prefix('about-us')->group(function () {
        Route::post('/update', [AboutUsController::class, 'update']); // Update About Us content
    });
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
