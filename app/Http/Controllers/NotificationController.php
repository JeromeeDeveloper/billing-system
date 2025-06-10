<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('notifications.index', compact('notifications'));
    }

    public function getUnreadCount()
    {
        $count = Notification::where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    public function getLatestNotifications()
    {
        $notifications = Notification::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'user_name' => $notification->user->name,
                    'time' => $notification->created_at->diffForHumans(),
                    'type' => $notification->type,
                    'is_read' => $notification->is_read
                ];
            });

        return response()->json($notifications);
    }

    public function markAsRead(Request $request)
    {
        if ($request->has('notification_id')) {
            Notification::where('id', $request->notification_id)
                ->update(['is_read' => true]);
        } else {
            Notification::where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json(['success' => true]);
    }

    public static function createNotification($type, $userId, $relatedId)
    {
        $user = Auth::user();
        $message = '';

        switch ($type) {
            case 'document_upload':
                $message = $user->name . ' has uploaded a new document';
                break;
            case 'billing_report':
                $message = $user->name . ' has generated a new billing report';
                break;
        }

        Notification::create([
            'type' => $type,
            'user_id' => $userId,
            'related_id' => $relatedId,
            'message' => $message
        ]);
    }
}
