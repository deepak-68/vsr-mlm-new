<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class RankController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $ranks = Rank::select('*');

            return DataTables::of($ranks)
                ->addIndexColumn()
                ->addColumn('is_active', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge bg-success">Yes</span>';
                    }
                    return '<span class="badge bg-danger">No</span>';
                })
                ->addColumn('actions', function ($row) {
                    $btn = '<button class="btn btn-sm btn-primary edit-rank-button me-1"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '"
                                data-slug="' . e($row->slug) . '"
                                data-required_self_cc="' . $row->required_self_cc . '"
                                data-sort_order="' . $row->sort_order . '"
                                data-reward_description="' . e($row->reward_description ?? '') . '"
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

        return view('admin.pages.ranks.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'slug'               => 'required|string|max:255|unique:ranks,slug',
            'required_self_cc'   => 'required|numeric',
            'sort_order'         => 'required|integer',
            'reward_description' => 'nullable|string',
            'is_active'          => 'boolean',
        ]);

        Rank::create($request->all());

        return response()->json(['success' => true, 'message' => 'Rank created successfully.']);
    }

    public function update(Request $request, $id)
    {
        $rank = Rank::findOrFail($id);

        $request->validate([
            'name'               => 'required|string|max:255',
            'slug'               => 'required|string|max:255|unique:ranks,slug,' . $id,
            'required_self_cc'   => 'required|numeric',
            'sort_order'         => 'required|integer',
            'reward_description' => 'nullable|string',
            'is_active'          => 'boolean',
        ]);

        $rank->update($request->all());

        return response()->json(['success' => true, 'message' => 'Rank updated successfully.']);
    }

    public function toggleActive($id)
    {
        $rank = Rank::findOrFail($id);
        $rank->update(['is_active' => !$rank->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Rank status toggled successfully.',
            'is_active' => $rank->fresh()->is_active,
        ]);
    }
}
