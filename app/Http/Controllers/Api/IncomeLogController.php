<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MlmUserResource;
use App\Models\IncomeLog;
use Illuminate\Http\Request;

class IncomeLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user_id;

        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User ID required'], 400);
        }

        $query = IncomeLog::with([
            'fromUser:id,user_name,first_name,last_name,track_id',
            'user:id',
        ])->where('user_id', $userId);

        if ($request->filled('income_type') && $request->income_type !== 'all') {
            $query->where('income_type', $request->income_type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->latest()->paginate($request->input('per_page', 20));

        $totals = (clone $query)->selectRaw('
            COALESCE(SUM(cc_amount), 0) as total_cc,
            COALESCE(SUM(currency_amount), 0) as total_currency
        ')->first();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'totals' => [
                'total_cc' => $totals->total_cc,
                'total_currency' => $totals->total_currency,
            ],
        ]);
    }
}
