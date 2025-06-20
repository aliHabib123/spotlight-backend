<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Submit a new contact form message
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $contactMessage = ContactMessage::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully. We will contact you soon.',
            'data' => $contactMessage
        ], 201);
    }

    /**
     * List all contact messages (admin only)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Show a specific contact message (admin only)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $message = ContactMessage::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Mark a message as read (admin only)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->update([
            'is_read' => true,
            'read_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
            'data' => $message
        ]);
    }

    /**
     * Delete a contact message (admin only)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $message = ContactMessage::findOrFail($id);
        $message->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }
}
