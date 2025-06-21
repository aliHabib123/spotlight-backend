<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AboutUsController extends Controller
{
    /**
     * Get the About Us page content.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function get()
    {
        // Get the first (and only) About Us record or return empty data
        $aboutUs = AboutUs::first();
        
        if (!$aboutUs) {
            return response()->json([
                'data' => [
                    'image' => null,
                    'text1' => null,
                    'text2' => null,
                    'text3' => null,
                    'slogan' => null,
                ]
            ]);
        }
        
        // Transform the image path to a full URL if it exists
        $aboutUsData = $aboutUs->toArray();
        if (!empty($aboutUsData['image']) && !filter_var($aboutUsData['image'], FILTER_VALIDATE_URL) && !str_starts_with($aboutUsData['image'], 'http')) {
            $aboutUsData['image'] = asset('storage/' . $aboutUsData['image']);
        }
        
        return response()->json([
            'data' => $aboutUsData
        ]);
    }
    
    /**
     * Create or update the About Us page content.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        // Check if user has permission to manage about us content
        Gate::authorize('manage about us');
        
        try {
            $validated = $request->validate([
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'text1' => 'nullable|string',
                'text2' => 'nullable|string',
                'text3' => 'nullable|string',
                'slogan' => 'nullable|string|max:255',
            ]);
            
            // Get the first About Us record or create a new one
            $aboutUs = AboutUs::first();
            if (!$aboutUs) {
                $aboutUs = new AboutUs();
            }
            
            // Handle image upload if provided
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($aboutUs->image && Storage::disk('public')->exists($aboutUs->image)) {
                    Storage::disk('public')->delete($aboutUs->image);
                }
                
                // Store the new image
                $path = $request->file('image')->store('about-us', 'public');
                $aboutUs->image = $path;
            }
            
            // Update text fields if provided
            if (isset($validated['text1'])) {
                $aboutUs->text1 = $validated['text1'];
            }
            
            if (isset($validated['text2'])) {
                $aboutUs->text2 = $validated['text2'];
            }
            
            if (isset($validated['text3'])) {
                $aboutUs->text3 = $validated['text3'];
            }
            
            if (isset($validated['slogan'])) {
                $aboutUs->slogan = $validated['slogan'];
            }
            
            $aboutUs->save();
            
            // Transform the image path to a full URL if it exists
            $aboutUsData = $aboutUs->toArray();
            if (!empty($aboutUsData['image']) && !filter_var($aboutUsData['image'], FILTER_VALIDATE_URL) && !str_starts_with($aboutUsData['image'], 'http')) {
                $aboutUsData['image'] = asset('storage/' . $aboutUsData['image']);
            }
            
            return response()->json([
                'message' => 'About Us content updated successfully',
                'data' => $aboutUsData
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
