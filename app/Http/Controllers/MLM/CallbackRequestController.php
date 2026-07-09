<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\CallbackRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CallbackRequestController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CallbackRequest::select(
                'callback_requests.*',
                'mlm_users.first_name',
                'mlm_users.last_name',
                'mlm_users.user_name',
                'mlm_users.phone'
            )
            ->leftJoin('mlm_users', 'callback_requests.mlm_user_id', '=', 'mlm_users.id');

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('name', fn($row) => $row->first_name . ' ' . $row->last_name)

                ->addColumn('username', fn($row) => $row->user_name)

                ->addColumn('phone', fn($row) => $row->phone ? '<a href="tel:' . e($row->phone) . '">' . e($row->phone) . '</a>' : '<span class="text-muted">—</span>')

                ->addColumn('issue_summary', fn($row) => $row->issue_summary
                    ? '<span title="' . e($row->issue_summary) . '">' . e(\Illuminate\Support\Str::limit($row->issue_summary, 40)) . '</span>'
                    : '<span class="text-muted">—</span>')

                ->addColumn('status', function ($row) {
                    $map = [
                        'PENDING'    => ['label' => 'Pending',   'class' => 'bg-warning text-dark'],
                        'SCHEDULED'  => ['label' => 'Scheduled', 'class' => 'bg-info'],
                        'COMPLETED'  => ['label' => 'Completed', 'class' => 'bg-success'],
                        'CANCELLED'  => ['label' => 'Cancelled', 'class' => 'bg-danger'],
                    ];
                    $item = $map[$row->status] ?? ['label' => ucfirst($row->status), 'class' => 'bg-secondary'];
                    return '<span class="badge ' . $item['class'] . '">' . $item['label'] . '</span>';
                })

                ->filterColumn('status', fn($query, $keyword) =>
                    $query->where('callback_requests.status', 'like', "%{$keyword}%")
                )

                ->addColumn('created_at', fn($row) => $row->created_at->format('d-m-Y'))

                ->addColumn('action', function ($row) {
                    $notes = e($row->admin_notes ?? '');
                    $name = e($row->first_name . ' ' . $row->last_name);
                    $userName = e($row->user_name);
                    $phone = e($row->phone ?? '');
                    $date = $row->preferred_date;
                    $time = $row->preferred_time;
                    $issue = e($row->issue_summary ?? '');
                    $status = e($row->status);

                    return '<button class="btn btn-sm btn-primary manage-btn"
                        data-id="' . $row->id . '"
                        data-status="' . $status . '"
                        data-notes="' . $notes . '"
                        data-name="' . $name . '"
                        data-username="' . $userName . '"
                        data-phone="' . $phone . '"
                        data-date="' . $date . '"
                        data-time="' . $time . '"
                        data-issue="' . $issue . '">
                        <i class="fas fa-tasks"></i> Manage
                    </button>';
                })

                ->rawColumns(['phone', 'issue_summary', 'status', 'action'])
                ->make(true);
        }

        return view('admin.pages.callback-requests.index');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:PENDING,SCHEDULED,COMPLETED,CANCELLED',
        ]);

        $callback = CallbackRequest::findOrFail($id);
        $callback->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
        ]);
    }

    public function updateNotes(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:5000',
        ]);

        $callback = CallbackRequest::findOrFail($id);
        $callback->update(['admin_notes' => $request->admin_notes]);

        return response()->json([
            'success' => true,
            'message' => 'Notes updated successfully.',
        ]);
    }
}
