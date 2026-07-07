<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MlmUserResource;
use App\Models\MlmUser;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);
        $user = MlmUser::findOrFail($request->user_id);
        $userId = $user->id;

        $query = Notification::with('user')->where('mlm_user_id', $userId);

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        $notifications = $query->latest()->paginate($request->input('per_page', 20));

        $unreadCount = Notification::where('mlm_user_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    // public function unreadCount(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required',
    //     ]);
    //     $user = MlmUser::findOrFail($request->user_id);
    //     $userId = $user->id;
    //         ->where('is_read', false)
    //         ->count();

    //     return response()->json([
    //         'success' => true,
    //         'unread_count' => $count,
    //     ]);
    // }

    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);
        $user = MlmUser::findOrFail($request->user_id);
        $userId = $user->id;

        Notification::where('mlm_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
