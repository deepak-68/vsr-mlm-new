<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TermsCondition;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class TermsConditionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $terms = TermsCondition::select('*');

            return DataTables::of($terms)
                ->addIndexColumn()
                ->addColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                })
                ->addColumn('description', function ($row) {
                    return Str::limit(strip_tags($row->description), 80);
                })
                ->addColumn('actions', function ($row) {
                    $btn = '<button class="btn btn-sm btn-primary edit-btn me-1"
                                data-id="' . $row->id . '"
                                data-sub_title="' . e($row->sub_title) . '"
                                data-main_title="' . e($row->main_title) . '"
                                data-description="' . e($row->description) . '"
                                data-is_active="' . $row->is_active . '">
                                <i class="fas fa-edit"></i>
                            </button>';
                    $btn .= '<button class="btn btn-sm btn-danger delete-btn"
                                data-id="' . $row->id . '">
                                <i class="fas fa-trash"></i>
                            </button>';
                    return $btn;
                })
                ->rawColumns(['is_active', 'actions'])
                ->make(true);
        }

        return view('admin.pages.admin-terms-conditions');
    }

    public function store(Request $request)
    {
        $request->validate([
            'sub_title'   => 'required|string|max:100',
            'main_title'  => 'required|string|max:200',
            'description' => 'required|string',
        ]);

        TermsCondition::create([
            'sub_title'   => $request->sub_title,
            'main_title'  => $request->main_title,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active'),
        ]);

        return response()->json(['success' => true, 'message' => 'Terms & Conditions created successfully.']);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'          => 'required|exists:terms_conditions,id',
            'sub_title'   => 'required|string|max:100',
            'main_title'  => 'required|string|max:200',
            'description' => 'required|string',
        ]);

        $term = TermsCondition::findOrFail($request->id);
        $term->update([
            'sub_title'   => $request->sub_title,
            'main_title'  => $request->main_title,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active'),
        ]);

        return response()->json(['success' => true, 'message' => 'Terms & Conditions updated successfully.']);
    }

    public function destroy($id)
    {
        TermsCondition::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Terms & Conditions deleted successfully.']);
    }
}
