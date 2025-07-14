<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::with('user')
            ->orderBy('created_at', 'desc');

        // Time filter
        $timeFilter = $request->input('time_filter', 'all');
        switch ($timeFilter) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'yesterday':
                $query->whereDate('created_at', today()->subDay());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'last_month':
                $query->whereMonth('created_at', now()->subMonth()->month)
                      ->whereYear('created_at', now()->subMonth()->year);
                break;
            case 'custom':
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                }
                break;
        }

        // Type filter
        $typeFilter = $request->input('type_filter');
        if ($typeFilter && $typeFilter !== 'all') {
            $query->where('type', $typeFilter);
        }

        // Status filter
        $statusFilter = $request->input('status_filter');
        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('is_read', $statusFilter === 'read');
        }

        // Per page options
        $perPage = $request->input('perPage', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100])) {
            $perPage = 15;
        }

        $notifications = $query->paginate($perPage)->appends([
            'time_filter' => $timeFilter,
            'type_filter' => $typeFilter,
            'status_filter' => $statusFilter,
            'perPage' => $perPage,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        // Get unique notification types for filter dropdown
        $notificationTypes = Notification::distinct()->pluck('type')->sort();

        return view('notifications.index', compact('notifications', 'timeFilter', 'typeFilter', 'statusFilter', 'perPage', 'notificationTypes'));
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
            case 'billing_approval':
                $billingPeriodFormatted = $billingPeriod ? \Carbon\Carbon::parse($billingPeriod)->format('F Y') : 'N/A';
                $message = 'You have approved your branch billing for billing period: ' . $billingPeriodFormatted;
                break;
            case 'billing_approval_cancelled':
                $billingPeriodFormatted = $billingPeriod ? \Carbon\Carbon::parse($billingPeriod)->format('F Y') : 'N/A';
                $message = 'You have cancelled your billing approval for billing period: ' . $billingPeriodFormatted;
                break;
            case 'file_backup':
                $billingPeriodFormatted = $billingPeriod ? \Carbon\Carbon::parse($billingPeriod)->format('F Y') : 'N/A';
                $message = $user->name . ' has created a file retention backup for billing period: ' . $billingPeriodFormatted;
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
