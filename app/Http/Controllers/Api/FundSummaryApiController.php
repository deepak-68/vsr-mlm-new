<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MlmUserResource;
use App\Models\FundSummary;
use App\Models\MlmUser;
use Illuminate\Http\Request;

class FundSummaryApiController extends Controller
{
    /**
     * Get fund summary with filters
     */
    public function index(Request $request)
    {
        try {
            $query = FundSummary::query();

            // Filter by user_id
            if ($request->filled('user_id')) {
                $userId = MlmUser::where('id', $request->user_id)->value('id');
                $query->where('user_id', $userId);
            }

            // Filter by type
            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('transaction_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('transaction_date', '<=', $request->date_to);
            }

            $fundSummaries = $query->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $fundSummaries->load('user');

            $data = $fundSummaries->map(fn($fs) => array_merge($fs->toArray(), []));

            // Calculate totals
            $totalCredit = $fundSummaries->sum('credit');
            $totalDebit = $fundSummaries->sum('debit');

            // Categorize deductions
            $deductionTypes = ['ADMIN DEBIT', 'Debit Transfer'];
            $deductionTotal = $fundSummaries->whereIn('type', $deductionTypes)->sum('debit');
            $purchaseTotal = $fundSummaries->where('type', 'Product Purchase')->sum('debit');

            return response()->json([
                'success' => true,
                'data' => $data,
                'totals' => [
                    'credit' => $totalCredit,
                    'debit' => $totalDebit,
                    'net' => $totalCredit - $totalDebit,
                    'deductions' => $deductionTotal,
                    'purchases' => $purchaseTotal,
                ],
                'message' => 'Fund summary fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fund summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}