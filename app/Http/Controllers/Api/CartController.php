<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartController extends Controller
{
    /**
     * Get the current user's cart or create a new one.
     */
    private function getOrCreateCart(Request $request)
    {
        if (Auth::check()) {
            // Logged in user - use their user_id
            $userId = Auth::id();
            $sessionId = null;
            
            // Check if there's a session cart to merge
            $cartSessionId = $request->header('X-Cart-Session');
            if ($cartSessionId) {
                return Cart::mergeSessionCartWithUserCart($cartSessionId, $userId);
            }
            
            return Cart::firstOrCreate(['user_id' => $userId]);
        } else {
            // Guest user - use session token from request header
            $sessionId = $request->header('X-Cart-Session');
            
            // If no session token exists yet, create one
            if (!$sessionId) {
                $sessionId = (string) Str::uuid();
                // We'll return this in the response
            }
            
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
            
            // Return both the cart and the session ID
            return ['cart' => $cart, 'session_id' => $sessionId];
        }
    }

    /**
     * Display the user's cart.
     */
    public function index(Request $request)
    {
        $result = $this->getOrCreateCart($request);
        
        $cart = $result instanceof Cart ? $result : $result['cart'];
        $sessionId = $result instanceof Cart ? null : $result['session_id'];
        
        // Load cart items with their related products and variations
        $cart->load(['items.product', 'items.variation']);
        
        $response = [
            'status' => 'success',
            'data' => [
                'cart' => $cart,
                'total_items' => $cart->getTotalItemsAttribute(),
                'total' => $cart->getTotalAttribute()
            ]
        ];
        
        // Include session ID for guest users
        if ($sessionId) {
            $response['session_id'] = $sessionId;
        }
        
        return response()->json($response);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'quantity' => 'required|integer|min:1',
            'attributes' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get the product
        $product = Product::findOrFail($request->product_id);
        
        // Check if product is active
        if (!$product->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product is not available'
            ], 400);
        }
        
        // Check if variation exists and is active (if provided)
        $variation = null;
        if ($request->product_variation_id) {
            $variation = ProductVariation::where('id', $request->product_variation_id)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();
                
            if (!$variation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product variation is not available'
                ], 400);
            }
            
            // Check stock if applicable
            if ($variation->stock !== null && $variation->stock < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough stock available',
                    'available_stock' => $variation->stock
                ], 400);
            }
        } else {
            // For single products, check stock if applicable
            if ($product->stock !== null && $product->stock < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough stock available',
                    'available_stock' => $product->stock
                ], 400);
            }
        }
        
        // Get or create the cart
        $result = $this->getOrCreateCart($request);
        $cart = $result instanceof Cart ? $result : $result['cart'];
        $sessionId = $result instanceof Cart ? null : $result['session_id'];
        
        // Check if the item already exists in the cart
        $cartItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variation_id', $request->product_variation_id)
            ->first();
            
        if ($cartItem) {
            // Update quantity if item already exists
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Create a new cart item
            $cartItem = new CartItem([
                'product_id' => $product->id,
                'product_variation_id' => $request->product_variation_id,
                'quantity' => $request->quantity,
                'attributes' => $request->attributes,
            ]);
            
            // Set the price based on the product or variation
            $cartItem->setPriceFromProduct();
            
            $cart->items()->save($cartItem);
        }
        
        // Load cart items with their related products and variations
        $cart->load(['items.product', 'items.variation']);
        
        $response = [
            'status' => 'success',
            'message' => 'Item added to cart',
            'data' => [
                'cart' => $cart,
                'total_items' => $cart->getTotalItemsAttribute(),
                'total' => $cart->getTotalAttribute()
            ]
        ];
        
        // Include session ID for guest users
        if ($sessionId) {
            $response['session_id'] = $sessionId;
        }
        
        return response()->json($response);
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItem(Request $request, string $itemId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get the cart
        $result = $this->getOrCreateCart($request);
        $cart = $result instanceof Cart ? $result : $result['cart'];
        $sessionId = $result instanceof Cart ? null : $result['session_id'];
        
        // Find the cart item
        $cartItem = $cart->items()->findOrFail($itemId);
        
        // Check stock if applicable
        if ($cartItem->product_variation_id) {
            $variation = ProductVariation::find($cartItem->product_variation_id);
            if ($variation && $variation->stock !== null && $variation->stock < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough stock available',
                    'available_stock' => $variation->stock
                ], 400);
            }
        } else {
            $product = Product::find($cartItem->product_id);
            if ($product && $product->stock !== null && $product->stock < $request->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Not enough stock available',
                    'available_stock' => $product->stock
                ], 400);
            }
        }
        
        // Update the quantity
        $cartItem->quantity = $request->quantity;
        $cartItem->save();
        
        // Load cart items with their related products and variations
        $cart->load(['items.product', 'items.variation']);
        
        $response = [
            'status' => 'success',
            'message' => 'Cart item updated',
            'data' => [
                'cart' => $cart,
                'total_items' => $cart->getTotalItemsAttribute(),
                'total' => $cart->getTotalAttribute()
            ]
        ];
        
        // Include session ID for guest users
        if ($sessionId) {
            $response['session_id'] = $sessionId;
        }
        
        return response()->json($response);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, string $itemId)
    {
        // Get the cart
        $result = $this->getOrCreateCart($request);
        $cart = $result instanceof Cart ? $result : $result['cart'];
        $sessionId = $result instanceof Cart ? null : $result['session_id'];
        
        // Find and delete the cart item
        $cartItem = $cart->items()->findOrFail($itemId);
        $cartItem->delete();
        
        // Load cart items with their related products and variations
        $cart->load(['items.product', 'items.variation']);
        
        $response = [
            'status' => 'success',
            'message' => 'Item removed from cart',
            'data' => [
                'cart' => $cart,
                'total_items' => $cart->getTotalItemsAttribute(),
                'total' => $cart->getTotalAttribute()
            ]
        ];
        
        // Include session ID for guest users
        if ($sessionId) {
            $response['session_id'] = $sessionId;
        }
        
        return response()->json($response);
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Request $request)
    {
        // Get the cart
        $result = $this->getOrCreateCart($request);
        $cart = $result instanceof Cart ? $result : $result['cart'];
        $sessionId = $result instanceof Cart ? null : $result['session_id'];
        
        // Delete all cart items
        $cart->items()->delete();
        
        $response = [
            'status' => 'success',
            'message' => 'Cart cleared',
            'data' => [
                'cart' => $cart,
                'total_items' => 0,
                'total' => 0
            ]
        ];
        
        // Include session ID for guest users
        if ($sessionId) {
            $response['session_id'] = $sessionId;
        }
        
        return response()->json($response);
    }
}
