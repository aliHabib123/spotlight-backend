<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ShippingAddressController extends Controller
{
    /**
     * Display a listing of the user's shipping addresses.
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
        
        $addresses = ShippingAddress::where('user_id', Auth::id())->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $addresses
        ]);
    }

    /**
     * Store a newly created shipping address.
     */
    public function store(Request $request)
    {
        // This endpoint requires authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state_province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50',
            'is_default' => 'boolean',
            'delivery_instructions' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $isDefault = $request->input('is_default', false);
        
        // If this is the first address or marked as default, update other addresses
        if ($isDefault) {
            ShippingAddress::where('user_id', Auth::id())
                ->update(['is_default' => false]);
        }
        
        // If this is the first address for the user, make it default regardless
        $addressCount = ShippingAddress::where('user_id', Auth::id())->count();
        if ($addressCount === 0) {
            $isDefault = true;
        }
        
        $address = new ShippingAddress($request->all());
        $address->user_id = Auth::id();
        $address->is_default = $isDefault;
        $address->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Shipping address created successfully',
            'data' => $address
        ], 201);
    }

    /**
     * Display the specified shipping address.
     */
    public function show(string $id)
    {
        // This endpoint requires authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        return response()->json([
            'status' => 'success',
            'data' => $address
        ]);
    }

    /**
     * Update the specified shipping address.
     */
    public function update(Request $request, string $id)
    {
        // This endpoint requires authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'address_line_1' => 'sometimes|required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'state_province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'country' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:50',
            'is_default' => 'boolean',
            'delivery_instructions' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // If setting as default, update other addresses
        if ($request->has('is_default') && $request->is_default) {
            ShippingAddress::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }
        
        $address->update($request->all());
        
        return response()->json([
            'status' => 'success',
            'message' => 'Shipping address updated successfully',
            'data' => $address
        ]);
    }

    /**
     * Set a shipping address as the default.
     */
    public function setDefault(string $id)
    {
        // This endpoint requires authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        // Update all addresses to not be default
        ShippingAddress::where('user_id', Auth::id())
            ->update(['is_default' => false]);
        
        // Set this address as default
        $address->is_default = true;
        $address->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Default shipping address updated',
            'data' => $address
        ]);
    }

    /**
     * Remove the specified shipping address.
     */
    public function destroy(string $id)
    {
        // This endpoint requires authentication
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated'
            ], 401);
        }
        
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $wasDefault = $address->is_default;
        
        $address->delete();
        
        // If this was the default address, set another address as default if available
        if ($wasDefault) {
            $newDefaultAddress = ShippingAddress::where('user_id', Auth::id())->first();
            if ($newDefaultAddress) {
                $newDefaultAddress->is_default = true;
                $newDefaultAddress->save();
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Shipping address deleted successfully'
        ]);
    }
}
