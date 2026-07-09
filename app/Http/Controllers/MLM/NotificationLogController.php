<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class NotificationLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $notifications = Notification::select(
                'notifications.*',
                'mlm_users.first_name',
                'mlm_users.last_name'
            )
            ->leftJoin('mlm_users', 'notifications.mlm_user_id', '=', 'mlm_users.id');

            if ($request->filled('type') && $request->type !== 'all') {
                $notifications->where('notifications.type', $request->type);
            }

            return DataTables::of($notifications)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                ->addColumn('type_display', function ($row) {
                    $icons = [
                        'purchase'     => 'fa-shopping-bag',
                        'income'       => 'fa-wallet',
                        'rank'         => 'fa-trophy',
                        'reward'       => 'fa-gift',
                        'registration' => 'fa-user-plus',
                        'withdrawal'   => 'fa-credit-card',
                        'ticket'       => 'fa-ticket-alt',
                    ];
                    $icon = $icons[$row->type] ?? 'fa-bell';
                    $label = $row->type ? ucfirst($row->type) : '—';
                    return '<span class="badge bg-primary rounded-circle p-2 me-1" style="font-size:11px"><i class="fas ' . $icon . '"></i></span> ' . $label;
                })
                ->addColumn('message', function ($row) {
                    if (!$row->message) return '<span class="text-muted">—</span>';
                    $full = e($row->message);
                    $truncated = e(Str::limit($row->message, 80));
                    return '<span title="' . $full . '">' . $truncated . '</span>';
                })
                ->addColumn('is_read', function ($row) {
                    if ($row->is_read) {
                        return '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Read</span>';
                    }
                    return '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Unread</span>';
                })
                ->addColumn('created_at', fn($row) => $row->created_at->format('d-m-Y H:i'))
                ->addColumn('action', function ($row) {
                    if ($row->is_read) return '';
                    return '<button class="btn btn-sm btn-outline-success mark-read" data-id="' . $row->id . '" title="Mark as read"><i class="fas fa-check-circle"></i></button>';
                })
                ->filterColumn('type', fn($query, $keyword) =>
                    $query->where('notifications.type', 'like', "%{$keyword}%")
                )
                ->rawColumns(['type_display', 'message', 'is_read', 'action'])
                ->make(true);
        }

        return view('admin.pages.notifications.index');
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'Marked as read.']);
    }

    public function recent()
    {
        $notifications = Notification::with('user')
            ->where('is_read', false)
            ->latest()
            ->paginate(5);

        $unreadCount = Notification::where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function unreadCount()
    {
        $count = Notification::where('is_read', false)->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markAllAsRead()
    {
        Notification::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true, 'message' => 'All notifications marked as read.']);
    }
}
