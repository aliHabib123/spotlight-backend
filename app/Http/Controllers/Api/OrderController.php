<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index()
    {
        // This endpoint requires authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $orders = Order::with(['items', 'shippingAddress'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Create a new order from the user's cart.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'payment_method' => 'required|string|max:50',
            'shipping_cost' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Get the cart
        if (Auth::check()) {
            // For authenticated users
            $cart = Cart::where('user_id', Auth::id())->first();
            
            // Verify shipping address belongs to user
            $shippingAddress = ShippingAddress::where('id', $request->shipping_address_id)
                ->where('user_id', Auth::id())
                ->first();
                
            if (!$shippingAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid shipping address'
                ], 400);
            }
        } else {
            // For guest users with session cart
            $sessionId = $request->header('X-Cart-Session');
            if (!$sessionId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No cart session found'
                ], 400);
            }
            
            $cart = Cart::where('session_id', $sessionId)->first();
            
            // For guest users, we need to create a shipping address
            $shippingAddress = ShippingAddress::find($request->shipping_address_id);
            if (!$shippingAddress) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid shipping address'
                ], 400);
            }
        }
        
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart is empty'
            ], 400);
        }
        
        // Start a database transaction
        try {
            DB::beginTransaction();
            
            // Create the order from the cart
            $order = Order::createFromCart(
                $cart,
                $shippingAddress,
                $request->shipping_cost ?? 0,
                $request->tax ?? 0,
                [
                    'payment_method' => $request->payment_method,
                    'notes' => $request->notes
                ]
            );
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order->load(['items', 'shippingAddress']),
                    'order_number' => $order->order_number
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(string $id)
    {
        // This endpoint requires authentication for user-specific orders
        if (Auth::check()) {
            $order = Order::with(['items', 'shippingAddress'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();
        } else {
            // For guest users, they can only view by order number and must provide additional verification
            $order = Order::with(['items', 'shippingAddress'])
                ->where('order_number', $id)
                ->first();
                
            // Additional verification could be implemented here
            // For example, requiring email or other identifying information
        }
        
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    /**
     * Get order status by order number.
     */
    public function getStatus(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->first();
        
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'order_number' => $order->order_number,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'created_at' => $order->created_at,
                'paid_at' => $order->paid_at,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at
            ]
        ]);
    }

    /**
     * Cancel an order (only if it's still pending).
     */
    public function cancel(Request $request, string $id)
    {
        // This endpoint requires authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $order = Order::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }
        
        // Check if order can be cancelled
        if ($order->order_status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order cannot be cancelled at this stage'
            ], 400);
        }
        
        // Cancel the order
        $order->markAsCancelled();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully',
            'data' => $order
        ]);
    }
}
