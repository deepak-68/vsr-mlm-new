<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rewards = Reward::select(
                'rewards.*',
                'ranks.name as rank_name'
            )
            ->leftJoin('ranks', 'rewards.rank_id', '=', 'ranks.id');

            return DataTables::of($rewards)
                ->addIndexColumn()
                ->addColumn('rank_name', fn($row) => $row->rank_name)
                ->addColumn('is_active', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge bg-success">Yes</span>';
                    }
                    return '<span class="badge bg-danger">No</span>';
                })
                ->addColumn('actions', function ($row) {
                    $btn = '<button class="btn btn-sm btn-primary edit-reward-button me-1"
                                data-id="' . $row->id . '"
                                data-rank_id="' . $row->rank_id . '"
                                data-name="' . e($row->name) . '"
                                data-description="' . e($row->description ?? '') . '"
                                data-value_cc="' . $row->value_cc . '"
                                data-reward_type="' . e($row->reward_type) . '"
                                data-is_active="' . $row->is_active . '">
                                <i class="fas fa-edit"></i> Edit
                            </button>';
                    $btn .= '<button class="btn btn-sm btn-warning toggle-active-button"
                                data-id="' . $row->id . '"
                                data-active="' . $row->is_active . '">
                                <i class="fas fa-toggle-on"></i> Toggle
                            </button>';
                    return $btn;
                })
                ->rawColumns(['is_active', 'actions'])
                ->make(true);
        }

        return view('admin.pages.rewards.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'rank_id'     => 'required|exists:ranks,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'value_cc'    => 'required|numeric',
            'reward_type' => 'required|string|max:255',
            'is_active'   => 'boolean',
        ]);

        Reward::create($request->all());

        return response()->json(['success' => true, 'message' => 'Reward created successfully.']);
    }

    public function update(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);

        $request->validate([
            'rank_id'     => 'required|exists:ranks,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'value_cc'    => 'required|numeric',
            'reward_type' => 'required|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $reward->update($request->all());

        return response()->json(['success' => true, 'message' => 'Reward updated successfully.']);
    }

    public function toggleActive($id)
    {
        $reward = Reward::findOrFail($id);
        $reward->update(['is_active' => !$reward->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Reward status toggled successfully.',
            'is_active' => $reward->fresh()->is_active,
        ]);
    }
}
