<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\UserRank;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UserRankController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $userRanks = UserRank::select(
                'user_ranks.*',
                'mlm_users.first_name',
                'mlm_users.last_name',
                'ranks.name as rank_name'
            )
            ->leftJoin('mlm_users', 'user_ranks.mlm_user_id', '=', 'mlm_users.id')
            ->leftJoin('ranks', 'user_ranks.rank_id', '=', 'ranks.id');

            return DataTables::of($userRanks)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                ->addColumn('rank_name', fn($row) => $row->rank_name)
                ->addColumn('is_current', function ($row) {
                    if ($row->is_current) {
                        return '<span class="badge bg-success">Yes</span>';
                    }
                    return '<span class="badge bg-secondary">No</span>';
                })
                ->addColumn('achieved_at', fn($row) => $row->achieved_at ? $row->achieved_at->format('d-m-Y') : '-')
                ->rawColumns(['is_current'])
                ->make(true);
        }

        return view('admin.pages.user-ranks.index');
    }
}
