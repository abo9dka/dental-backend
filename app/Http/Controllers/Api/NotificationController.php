<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get();

        return $this->successResponse(
            $notifications,
            'Notifications retrieved successfully.'
        );
    }

    public function unread(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->where('is_read', false)
            ->latest()
            ->get();

        return $this->successResponse(
            $notifications,
            'Unread notifications retrieved successfully.'
        );
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->find($id);

        if (!$notification) {
            return $this->errorResponse(
                'Notification not found.',
                404
            );
        }

        $notification->update([
            'is_read' => true,
        ]);

        return $this->successResponse(
            $notification->fresh(),
            'Notification marked as read.'
        );
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
            ]);

        return $this->successResponse(
            null,
            'All notifications marked as read.'
        );
    }
}
