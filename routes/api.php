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
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SavedNewsController;
use App\Http\Controllers\Api\SavedSpotlightController;
use App\Http\Controllers\Api\SavedEventController;
use App\Http\Controllers\Api\ShippingAddressController;
use App\Http\Controllers\Api\ShopCategoryController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SpotlightController;
use App\Http\Controllers\Api\SpotlightCategoryController;
use App\Http\Controllers\Api\SpotlightRatingController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventCategoryController;
use App\Http\Controllers\Api\EventLocationController;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\TourLocationController;
use App\Http\Controllers\Api\TourBookingController;
use App\Http\Controllers\Api\WhishCallbackController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\FcmTokenController;
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

// Whish callbacks (public, no version prefix) to match config default URLs
Route::get('/whish/callback/success', [WhishCallbackController::class, 'success']);
Route::get('/whish/callback/failure', [WhishCallbackController::class, 'failure']);

// JWT Authentication routes for mobile app
Route::prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    
    // Social login routes
    Route::post('google', [SocialController::class, 'googleLogin']);
    Route::post('facebook', [SocialController::class, 'facebookLogin']);
    
    // Email verification routes
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('api.verification.verify');
    Route::post('email/resend', [AuthController::class, 'resendVerificationEmail']);
    
    // Password reset routes
    Route::post('request-reset-otp', [PasswordResetController::class, 'requestResetOtp']);
    Route::post('verify-reset-otp', [PasswordResetController::class, 'verifyResetOtp']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);

    // Protected JWT routes
    Route::middleware([\App\Http\Middleware\JsonApiAuthentication::class . ':api'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('update-profile', [AuthController::class, 'updateProfile']);
        Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
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
    Route::get('/spotlights/latest', [SpotlightController::class, 'latest']);
    Route::get('/spotlights/{spotlight}', [SpotlightController::class, 'show']);
    Route::get('/spotlights/category/{category}', [SpotlightController::class, 'byCategory']);
    Route::get('/spotlights/{spotlight}/attributes', [SpotlightController::class, 'attributes']);

    // Event Categories
    Route::get('/event-categories', [EventCategoryController::class, 'index']);
    Route::get('/event-categories/all', [EventCategoryController::class, 'all']);
    Route::get('/event-categories/{category}', [EventCategoryController::class, 'show']);

    // Event Locations
    Route::get('/event-locations', [EventLocationController::class, 'index']);
    Route::get('/event-locations/all', [EventLocationController::class, 'all']);
    Route::get('/event-locations/{location}', [EventLocationController::class, 'show']);
    Route::get('/event-locations/{location}/events', [EventLocationController::class, 'events']);

    // Events
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/featured', [EventController::class, 'featured']);
    Route::get('/events/upcoming', [EventController::class, 'upcoming']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/events/category/{category}', [EventController::class, 'byCategory']);
    
    // Tour Locations
    Route::get('/tour-locations', [TourLocationController::class, 'index']);
    Route::get('/tour-locations/{id}', [TourLocationController::class, 'show']);
    Route::get('/tour-locations/{id}/tours', [TourLocationController::class, 'tours']);
    
    // Tours
    Route::get('/tours', [TourController::class, 'index']);
    Route::get('/tours/{id}', [TourController::class, 'show']);
    Route::get('/tours/{id}/ratings', [TourController::class, 'getRatings']);
    // Tour Bookings - Public endpoints
    Route::post('/tours/{id}/book', [TourBookingController::class, 'store']);
    Route::get('/tour-bookings/status/{bookingNumber}', [TourBookingController::class, 'status']);
    Route::post('/tour-bookings/{bookingNumber}/payment-link', [TourBookingController::class, 'publicPaymentLink']);

    // Tags
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/tags/{tag}/spotlights', [TagController::class, 'spotlights']);

    // Locations
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{location}/spotlights', [LocationController::class, 'spotlights']);

    // Attribute Definitions - Read only for public
    Route::get('/attributes', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'index']);
    Route::get('/attributes/{attribute}', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'show']);
    Route::get('/hierarchical-filters', [\App\Http\Controllers\Api\SpotlightAttributeDefinitionController::class, 'getHierarchicalFilters']);

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
    
    // FCM Tokens - Public (for device registration)
    Route::post('/fcm-tokens', [FcmTokenController::class, 'store']);

    Route::get('/weather', [WeatherController::class, 'show']);
});

// Protected API routes
Route::middleware([\App\Http\Middleware\JsonApiAuthentication::class . ':api'])->prefix('v1')->group(function () {
    // Tour Rating
    Route::post('/tours/{id}/rate', [TourController::class, 'rate']);
    Route::get('/tours/{id}/rating', [TourController::class, 'checkRating']);
    // Tour Bookings - Authenticated user endpoints
    Route::prefix('tour-bookings')->group(function () {
        Route::get('/', [TourBookingController::class, 'index']);
        Route::get('/{id}', [TourBookingController::class, 'show']);
        Route::patch('/{id}/cancel', [TourBookingController::class, 'cancel']);
        Route::get('/{id}/payment-status', [TourBookingController::class, 'paymentStatus']);
    });
    
    // Tour Management (for Tour Admins)
    Route::post('/tours', [TourController::class, 'store']);
    Route::put('/tours/{id}', [TourController::class, 'update']);
    Route::delete('/tours/{id}', [TourController::class, 'destroy']);
    
    // Tour Approval (for Admins only)
    Route::patch('/tours/{id}/approve', [TourController::class, 'approve']);
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
    
    // Saved News (authenticated users only)
    Route::prefix('saved-news')->group(function () {
        Route::get('/', [SavedNewsController::class, 'index']); // List user's saved news
        Route::post('/{id}', [SavedNewsController::class, 'save']); // Save a news item
        Route::delete('/{id}', [SavedNewsController::class, 'unsave']); // Unsave a news item
        Route::get('/{id}/check', [SavedNewsController::class, 'check']); // Check if a news item is saved
    });

    // Saved Events (authenticated users only)
    Route::prefix('saved-events')->group(function () {
        Route::get('/', [SavedEventController::class, 'index']); // List user's saved events
        Route::post('/{id}', [SavedEventController::class, 'save']); // Save an event
        Route::delete('/{id}', [SavedEventController::class, 'unsave']); // Unsave an event
        Route::get('/{id}/check', [SavedEventController::class, 'check']); // Check if an event is saved
    });
    
    // Spotlight Ratings (authenticated users only)
    Route::prefix('spotlight-ratings')->group(function () {
        Route::get('/{id}', [SpotlightRatingController::class, 'index']); // List ratings for a spotlight
        Route::post('/{id}', [SpotlightRatingController::class, 'rate']); // Rate a spotlight
        Route::put('/{id}', [SpotlightRatingController::class, 'update']); // Update existing rating
        Route::delete('/{id}', [SpotlightRatingController::class, 'delete']); // Delete a rating
        Route::get('/{id}/check', [SpotlightRatingController::class, 'check']); // Check if user has rated a spotlight
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
