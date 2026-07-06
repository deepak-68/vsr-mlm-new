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

            return DataTables::of($notifications)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                ->addColumn('message', function ($row) {
                    return Str::limit($row->message, 80);
                })
                ->addColumn('is_read', function ($row) {
                    if ($row->is_read) {
                        return '<span class="badge bg-success">Read</span>';
                    }
                    return '<span class="badge bg-warning text-dark">Unread</span>';
                })
                ->addColumn('created_at', fn($row) => $row->created_at->format('d-m-Y H:i'))
                ->rawColumns(['is_read'])
                ->make(true);
        }

        return view('admin.pages.notifications.index');
    }
}
