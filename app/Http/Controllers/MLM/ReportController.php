<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function purchaseReport(Request $request)
    {
        if ($request->ajax()) {
            $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->query('date_to', now()->toDateString());

            $query = $this->reportService->getPurchaseReport($dateFrom, $dateTo);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user?->user_name . ' (' . ($row->user?->first_name . ' ' . $row->user?->last_name) . ')')
                ->addColumn('items_count', fn($row) => $row->items->sum('quantity'))
                ->addColumn('total_amount', fn($row) => '₹' . number_format($row->total_amount, 2))
                ->addColumn('cc_amount', fn($row) => number_format($row->total_cc_points, 2))
                ->addColumn('status', function ($row) {
                    $map = [
                        'COMPLETED' => ['label' => 'Completed', 'class' => 'bg-success'],
                        'PENDING' => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                        'CANCELLED' => ['label' => 'Cancelled', 'class' => 'bg-danger'],
                    ];
                    $item = $map[$row->status] ?? ['label' => ucfirst($row->status), 'class' => 'bg-secondary'];
                    return '<span class="badge ' . $item['class'] . '">' . $item['label'] . '</span>';
                })
                ->addColumn('date', fn($row) => $row->created_at->format('d-m-Y'))
                ->rawColumns(['status'])
                ->with([
                    'totalOrders' => $query->count(),
                    'totalAmount' => '₹' . number_format($query->sum('total_amount'), 2),
                    'totalCC' => number_format($query->sum('total_cc_points'), 2),
                ])
                ->make(true);
        }

        return view('admin.pages.reports.purchase');
    }

    public function incomeReport(Request $request)
    {
        if ($request->ajax()) {
            $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->query('date_to', now()->toDateString());
            $type = $request->query('income_type');

            $query = $this->reportService->getIncomeReport($dateFrom, $dateTo, $type);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user?->user_name)
                ->addColumn('from_user_name', fn($row) => $row->fromUser?->user_name ?? '-')
                ->addColumn('income_type', fn($row) => ucwords(str_replace('_', ' ', $row->income_type)))
                ->addColumn('cc_amount', fn($row) => number_format($row->cc_amount, 2))
                ->addColumn('currency_amount', fn($row) => '₹' . number_format($row->currency_amount, 2))
                ->addColumn('reference', fn($row) => $row->order_number ?: ($row->reference_type ? $row->reference_type . ' #' . $row->reference_id : '-'))
                ->addColumn('date', fn($row) => $row->created_at->format('d-m-Y'))
                ->with([
                    'totalCC' => number_format($query->sum('cc_amount'), 2),
                    'totalCurrency' => '₹' . number_format($query->sum('currency_amount'), 2),
                ])
                ->make(true);
        }

        return view('admin.pages.reports.income');
    }

    public function referralIncomeReport(Request $request)
    {
        if ($request->ajax()) {
            $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->query('date_to', now()->toDateString());

            $query = $this->reportService->getReferralIncomeReport($dateFrom, $dateTo);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user?->user_name)
                ->addColumn('from_user_name', fn($row) => $row->fromUser?->user_name ?? '-')
                ->addColumn('cc_amount', fn($row) => number_format($row->cc_amount, 2))
                ->addColumn('currency_amount', fn($row) => '₹' . number_format($row->currency_amount, 2))
                ->addColumn('order_no', fn($row) => $row->order_number ?? '-')
                ->addColumn('date', fn($row) => $row->created_at->format('d-m-Y'))
                ->with([
                    'totalCC' => number_format($query->sum('cc_amount'), 2),
                    'totalCurrency' => '₹' . number_format($query->sum('currency_amount'), 2),
                ])
                ->make(true);
        }

        return view('admin.pages.reports.referral-income');
    }

    public function rewardAchievementReport(Request $request)
    {
        if ($request->ajax()) {
            $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->query('date_to', now()->toDateString());

            $query = $this->reportService->getRewardAchievementReport($dateFrom, $dateTo);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user?->user_name)
                ->addColumn('reward_name', fn($row) => $row->reward?->name ?? '-')
                ->addColumn('rank_name', fn($row) => $row->rank?->name ?? '-')
                ->addColumn('achieved_at', fn($row) => $row->achieved_at?->format('d-m-Y') ?? '-')
                ->addColumn('claimed_at', fn($row) => $row->claimed_at?->format('d-m-Y') ?? '-')
                ->addColumn('status', function ($row) {
                    $map = [
                        'claimed' => ['label' => 'Claimed', 'class' => 'bg-success'],
                        'pending' => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-danger'],
                    ];
                    $item = $map[$row->status] ?? ['label' => ucfirst($row->status), 'class' => 'bg-secondary'];
                    return '<span class="badge ' . $item['class'] . '">' . $item['label'] . '</span>';
                })
                ->rawColumns(['status'])
                ->with([
                    'totalAchievements' => $query->count(),
                    'totalClaimed' => $query->where('status', 'claimed')->count(),
                ])
                ->make(true);
        }

        return view('admin.pages.reports.reward-achievement');
    }

    public function rankAchievementReport(Request $request)
    {
        if ($request->ajax()) {
            $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->query('date_to', now()->toDateString());

            $query = $this->reportService->getRankAchievementReport($dateFrom, $dateTo);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user?->user_name)
                ->addColumn('rank_name', fn($row) => $row->rank?->name ?? '-')
                ->addColumn('cc_at_time', fn($row) => number_format($row->current_cc_at_time, 2))
                ->addColumn('is_current', function ($row) {
                    return $row->is_current
                        ? '<span class="badge bg-success">Current</span>'
                        : '<span class="badge bg-secondary">Previous</span>';
                })
                ->addColumn('achieved_at', fn($row) => $row->achieved_at?->format('d-m-Y') ?? '-')
                ->rawColumns(['is_current'])
                ->with([
                    'totalAchievements' => $query->count(),
                ])
                ->make(true);
        }

        return view('admin.pages.reports.rank-achievement');
    }

    public function withdrawalReport(Request $request)
    {
        if ($request->ajax()) {
            $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->query('date_to', now()->toDateString());

            $query = $this->reportService->getWithdrawalReport($dateFrom, $dateTo);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user?->user_name)
                ->addColumn('amount', fn($row) => '₹' . number_format($row->amount, 2))
                ->addColumn('charges', fn($row) => '₹0.00')
                ->addColumn('payable', fn($row) => '₹' . number_format($row->amount, 2))
                ->addColumn('status', function ($row) {
                    $map = [
                        'approved' => ['label' => 'Approved', 'class' => 'bg-success'],
                        'pending' => ['label' => 'Pending', 'class' => 'bg-warning text-dark'],
                        'rejected' => ['label' => 'Rejected', 'class' => 'bg-danger'],
                        'paid' => ['label' => 'Paid', 'class' => 'bg-info'],
                    ];
                    $item = $map[$row->status] ?? ['label' => ucfirst($row->status), 'class' => 'bg-secondary'];
                    return '<span class="badge ' . $item['class'] . '">' . $item['label'] . '</span>';
                })
                ->addColumn('transaction_no', fn($row) => $row->transaction_no ?? '-')
                ->addColumn('payment_date', fn($row) => $row->deposit_date?->format('d-m-Y') ?? '-')
                ->addColumn('request_date', fn($row) => $row->created_at->format('d-m-Y'))
                ->rawColumns(['status'])
                ->with([
                    'totalRequests' => $query->count(),
                    'totalAmount' => '₹' . number_format($query->sum('amount'), 2),
                    'totalPayable' => '₹' . number_format($query->sum('amount'), 2),
                ])
                ->make(true);
        }

        return view('admin.pages.reports.withdrawal');
    }

    public function userActivityReport(Request $request)
    {
        if ($request->ajax()) {
            $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
            $dateTo = $request->query('date_to', now()->toDateString());
            $tab = $request->query('tab', 'notifications');

            if ($tab === 'orders') {
                $query = \App\Models\Order::with('user')
                    ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

                return DataTables::of($query)
                    ->addIndexColumn()
                    ->addColumn('user_name', fn($row) => $row->user?->user_name)
                    ->addColumn('activity_type', fn() => 'Order')
                    ->addColumn('description', fn($row) => 'Order #' . $row->order_number . ' - ' . $row->status)
                    ->addColumn('date', fn($row) => $row->created_at->format('d-m-Y'))
                    ->with([
                        'totalActivities' => $query->count(),
                    ])
                    ->make(true);
            }

            $query = $this->reportService->getUserActivityReport($dateFrom, $dateTo);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('user_name', fn($row) => $row->user?->user_name)
                ->addColumn('activity_type', fn($row) => ucfirst($row->type ?? 'Notification'))
                ->addColumn('description', fn($row) => $row->title . ($row->message ? ': ' . $row->message : ''))
                ->addColumn('date', fn($row) => $row->created_at->format('d-m-Y'))
                ->with([
                    'totalActivities' => $query->count(),
                ])
                ->make(true);
        }

        return view('admin.pages.reports.user-activity');
    }
}
