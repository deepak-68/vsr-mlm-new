<?php

namespace App\Http\Controllers\MLM;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PurchaseHistoryController extends Controller
{
    public function pendingOrders(Request $request)
    {
        if ($request->ajax()) {
            $orders = Order::select(
                'orders.*',
                'mlm_users.first_name',
                'mlm_users.last_name'
            )
            ->leftJoin('mlm_users', 'orders.user_id', '=', 'mlm_users.id')
            ->where('orders.status', Order::STATUS_PENDING)
            ->where('orders.payment_mode', Order::PAYMENT_MANUAL)
            ->withCount('items');

            return DataTables::of($orders)
                ->addIndexColumn()
                ->addColumn('order_no', fn($row) => '#' . str_pad($row->id, 6, '0', STR_PAD_LEFT))
                ->addColumn('user_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                ->addColumn('total_amount', fn($row) => '₹' . number_format($row->total_amount, 2))
                ->addColumn('cc_amount', fn($row) => $row->total_cc_points ?? 0)
                ->addColumn('items_count', fn($row) => $row->items_count)
                ->addColumn('payment_mode', fn($row) => '<span class="badge bg-secondary">' . $row->payment_mode . '</span>')
                ->addColumn('status', fn($row) => '<span class="badge bg-warning text-dark">Pending</span>')
                ->addColumn('date', fn($row) => $row->order_date ? $row->order_date->format('d-m-Y') : $row->created_at->format('d-m-Y'))
                ->addColumn('actions', function ($row) {
                    return '<button class="btn btn-sm btn-primary view-order-button"
                                data-id="' . $row->id . '">
                                <i class="fas fa-eye"></i> View
                            </button>';
                })
                ->rawColumns(['status', 'payment_mode', 'actions'])
                ->make(true);
        }

        return view('admin.pages.purchase-history.pending');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $orders = Order::select(
                'orders.*',
                'mlm_users.first_name',
                'mlm_users.last_name'
            )
            ->leftJoin('mlm_users', 'orders.user_id', '=', 'mlm_users.id')
            ->withCount('items');

            return DataTables::of($orders)
                ->addIndexColumn()
                ->addColumn('order_no', fn($row) => '#' . str_pad($row->id, 6, '0', STR_PAD_LEFT))
                ->addColumn('user_name', fn($row) => $row->first_name . ' ' . $row->last_name)
                ->addColumn('total_amount', fn($row) => '₹' . number_format($row->total_amount, 2))
                ->addColumn('cc_amount', fn($row) => $row->total_cc_points ?? 0)
                ->addColumn('items_count', fn($row) => $row->items_count)
                ->addColumn('payment_mode', fn($row) => '<span class="badge bg-secondary">' . $row->payment_mode . '</span>')
                ->addColumn('status', function ($row) {
                    $map = [
                        'PENDING'         => ['label' => 'Pending',         'class' => 'bg-warning text-dark'],
                        'PAYMENT_FAILED'  => ['label' => 'Payment Failed',  'class' => 'bg-danger'],
                        'CONFIRMED'       => ['label' => 'Confirmed',       'class' => 'bg-info text-dark'],
                        'PACKED'          => ['label' => 'Packed',          'class' => 'bg-primary'],
                        'SHIPPED'         => ['label' => 'Shipped',         'class' => 'bg-primary'],
                        'DELIVERED'       => ['label' => 'Delivered',       'class' => 'bg-success'],
                        'CANCELLED'       => ['label' => 'Cancelled',       'class' => 'bg-danger'],
                        'RETURN_REQUESTED' => ['label' => 'Return Requested','class' => 'bg-warning text-dark'],
                        'RETURNED'        => ['label' => 'Returned',        'class' => 'bg-secondary'],
                        'REFUNDED'        => ['label' => 'Refunded',        'class' => 'bg-success'],
                        'PARTIALLY_REFUNDED' => ['label' => 'Partially Refunded', 'class' => 'bg-warning text-dark'],
                    ];
                    $item = $map[$row->status] ?? ['label' => ucfirst($row->status), 'class' => 'bg-secondary'];
                    return '<span class="badge ' . $item['class'] . '">' . $item['label'] . '</span>';
                })
                ->addColumn('date', fn($row) => $row->order_date ? $row->order_date->format('d-m-Y') : $row->created_at->format('d-m-Y'))
                ->addColumn('actions', function ($row) {
                    return '<button class="btn btn-sm btn-primary view-order-button"
                                data-id="' . $row->id . '">
                                <i class="fas fa-eye"></i> View
                            </button>';
                })
                ->rawColumns(['status', 'payment_mode', 'actions'])
                ->make(true);
        }

        return view('admin.pages.purchase-history.index');
    }

    public function show($id)
    {
        $order = Order::with(['user', 'purchasedForUser', 'items.product'])->findOrFail($id);

        $paymentProofUrl = null;
        if ($order->payment_proof) {
            $paymentProofUrl = asset('storage/' . $order->payment_proof);
        }

        return response()->json([
            'success' => true,
            'order'   => $order,
            'payment_proof_url' => $paymentProofUrl,
        ]);
    }

    public function confirmOrder($id, OrderConfirmationService $confirmationService)
    {
        try {
            $result = $confirmationService->confirm($id);

            return response()->json($result);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Confirm Order Error: ' . $e->getMessage(), [
                'order_id' => $id,
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm order. Check logs for details.',
            ], 500);
        }
    }
}
