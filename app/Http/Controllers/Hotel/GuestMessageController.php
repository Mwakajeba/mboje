<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel\GuestMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GuestMessageController extends Controller
{
    public function index()
    {
        $this->authorize('view hotel management');
        
        // Get branch_id from session or user, same pattern as BookingController
        $branchId = session('branch_id') ?? Auth::user()->branch_id ?? null;
        
        // Get messages for the current branch only
        $query = GuestMessage::with(['branch', 'readBy', 'respondedBy']);
        
        if ($branchId) {
            // Show messages for this specific branch only
            $query->where('branch_id', $branchId);
        } else {
            // If no branch is set, show messages without branch_id
            $query->whereNull('branch_id');
        }
        
        $messages = $query->orderBy('is_read', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $unreadQuery = GuestMessage::query();
        if ($branchId) {
            $unreadQuery->where('branch_id', $branchId);
        } else {
            $unreadQuery->whereNull('branch_id');
        }
        
        $unreadCount = $unreadQuery->where('is_read', false)->count();
        
        return view('hotel.guest-messages.index', compact('messages', 'unreadCount'));
    }

    public function show(GuestMessage $message)
    {
        $this->authorize('view hotel management');
        
        // Mark as read if not already read
        if (!$message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
                'read_by' => auth()->id(),
            ]);
        }
        
        $message->load(['branch', 'readBy', 'respondedBy']);
        
        return view('hotel.guest-messages.show', compact('message'));
    }

    public function respond(Request $request, GuestMessage $message)
    {
        $this->authorize('view hotel management');
        
        $request->validate([
            'response' => 'required|string|max:5000',
        ]);

        DB::beginTransaction();
        try {
            $message->update([
                'response' => $request->response,
                'responded_at' => now(),
                'responded_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('hotel.guest-messages.show', $message)
                ->with('success', 'Response sent successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to send response: ' . $e->getMessage());
        }
    }

    public function markAsRead(GuestMessage $message)
    {
        $this->authorize('view hotel management');
        
        if (!$message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
                'read_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
        ]);
    }
}
