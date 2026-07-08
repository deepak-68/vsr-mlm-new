<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\IncomeLog;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReferralIncomeLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $logs = IncomeLog::select(
                'income_logs.*',
                'u1.first_name as user_first_name',
                'u1.last_name as user_last_name',
                'u1.user_name as user_user_name',
                'u2.first_name as from_user_first_name',
                'u2.last_name as from_user_last_name',
                'u2.user_name as from_user_user_name'
            )
            ->leftJoin('mlm_users as u1', 'income_logs.user_id', '=', 'u1.id')
            ->leftJoin('mlm_users as u2', 'income_logs.from_user_id', '=', 'u2.id');

            if ($request->filled('income_type')) {
                $logs->where('income_logs.income_type', $request->income_type);
            }

            return DataTables::of($logs)
                ->addIndexColumn()
                ->addColumn('user', fn($row) => $row->user_first_name . ' ' . $row->user_last_name . ' (' . $row->user_user_name . ')')
                ->addColumn('from_user', fn($row) => $row->from_user_first_name
                    ? $row->from_user_first_name . ' ' . $row->from_user_last_name . ' (' . $row->from_user_user_name . ')'
                    : '-')
                ->addColumn('currency_amount', fn($row) => '₹' . number_format($row->currency_amount, 2))
                ->addColumn('balance_after', fn($row) => '₹' . number_format($row->balance_after, 2))
                ->addColumn('created_at', fn($row) => $row->created_at->format('d-m-Y H:i'))
                ->filterColumn('user', fn($query, $keyword) =>
                    $query->whereRaw("CONCAT(u1.first_name, ' ', u1.last_name) like ?", ["%{$keyword}%"])
                )
                ->filterColumn('from_user', fn($query, $keyword) =>
                    $query->whereRaw("CONCAT(u2.first_name, ' ', u2.last_name) like ?", ["%{$keyword}%"])
                )
                ->make(true);
        }

        return view('admin.pages.referral-income-logs.index', [
            'selectedIncomeType' => $request->income_type ?? '',
        ]);
    }
}
