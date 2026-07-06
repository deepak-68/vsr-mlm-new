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
                'mlm_users.user_name'
            )
            ->leftJoin('mlm_users', 'callback_requests.mlm_user_id', '=', 'mlm_users.id');

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('name', fn($row) => $row->first_name . ' ' . $row->last_name)

                ->addColumn('username', fn($row) => $row->user_name)

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
                    $statuses = ['PENDING', 'SCHEDULED', 'COMPLETED', 'CANCELLED'];
                    $options = '';
                    foreach ($statuses as $s) {
                        $selected = $s === $row->status ? 'selected' : '';
                        $options .= "<option value=\"{$s}\" {$selected}>{$s}</option>";
                    }

                    return '
                        <div class="d-flex align-items-center gap-1">
                            <select class="form-select form-select-sm status-select" data-id="' . $row->id . '" style="width:130px">
                                ' . $options . '
                            </select>
                            <button class="btn btn-sm btn-primary update-status-btn" data-id="' . $row->id . '">
                                <i class="fas fa-save"></i>
                            </button>
                            <button class="btn btn-sm btn-info notes-btn" data-id="' . $row->id . '" data-notes="' . e($row->admin_notes ?? '') . '">
                                <i class="fas fa-sticky-note"></i>
                            </button>
                        </div>';
                })

                ->rawColumns(['status', 'action'])
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
