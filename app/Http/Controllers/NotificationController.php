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
                    'billing_period' => $notification->billing_period ? \Carbon\Carbon::parse($notification->billing_period)->format('F Y') : 'N/A',
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
        $billingPeriod = $user->billing_period;
        $message = '';

        switch ($type) {
            case 'document_upload':
                $billingPeriodFormatted = $billingPeriod ? \Carbon\Carbon::parse($billingPeriod)->format('F Y') : 'N/A';
                $message = $user->name . ' has uploaded a new document for billing period: ' . $billingPeriodFormatted;
                break;
            case 'billing_report':
                $billingPeriodFormatted = $billingPeriod ? \Carbon\Carbon::parse($billingPeriod)->format('F Y') : 'N/A';
                $message = $user->name . ' has generated a new billing report for billing period: ' . $billingPeriodFormatted;
                break;
            case 'billing_period_update':
                $billingPeriodFormatted = $billingPeriod ? \Carbon\Carbon::parse($billingPeriod)->format('F Y') : 'N/A';
                $message = 'Your billing period has been automatically updated to: ' . $billingPeriodFormatted;
                break;
        }

        Notification::create([
            'type' => $type,
            'user_id' => $userId,
            'related_id' => $relatedId,
            'message' => $message,
            'billing_period' => $billingPeriod
        ]);
    }
}
