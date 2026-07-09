<?php

namespace App\Http\Controllers\Api;

use App\Services\PurchaseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PurchaseProductRequest;
use App\Http\Resources\MlmUserResource;
use App\Models\MlmUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\AdminBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function purchase(PurchaseProductRequest $request)
    {
        try {
            $order = $this->purchaseService->purchase(
                $request->user_id,
                $request->product_id,
                $request->quantity
            );

            $order->load('user');

            $invoice = Invoice::where('order_id', $order->id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Product purchased successfully.',
                'data' => $order,
                'invoice' => $invoice,
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Purchase Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Purchase failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function manualPurchase(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:mlm_users,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:2',
            'transaction_number' => 'required|string|max:255',
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'target_track_id' => 'nullable|string',
        ]);

        try {
            $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');

            if ($request->filled('target_track_id')) {
                $targetUser = MlmUser::where('track_id', $request->target_track_id)
                    ->orWhere('user_name', $request->target_track_id)
                    ->where('is_deleted', false)
                    ->first();

                if (!$targetUser) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Target user not found.',
                    ], 404);
                }

                if ($targetUser->id == $request->user_id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Target user is yourself. Use regular purchase.',
                    ], 422);
                }

                $order = $this->purchaseService->purchase(
                    $targetUser->id,
                    $request->product_id,
                    $request->quantity,
                    $request->user_id,
                    Order::PAYMENT_MANUAL,
                    $request->transaction_number,
                    $paymentProofPath
                );
            } else {
                $order = $this->purchaseService->purchase(
                    $request->user_id,
                    $request->product_id,
                    $request->quantity,
                    null,
                    Order::PAYMENT_MANUAL,
                    $request->transaction_number,
                    $paymentProofPath
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully. Awaiting admin confirmation.',
                'data' => $order,
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Manual Purchase Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Purchase failed.',
                'error' => $e->getMessage()
            ], 500);           
        }
    }
    

    public function orderForSomeone(Request $request)
    {
        $request->validate([
            'ordering_user_id' => 'required',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:2',
        ]);

        $orderingUser = MlmUser::findOrFail($request->ordering_user_id);

        // Resolve target user from either target_user_id or target_track_id
        $targetUserId = $request->target_user_id;

        if (!$targetUserId && $request->filled('target_track_id')) {
            $targetUser = MlmUser::where('track_id', $request->target_track_id)
                ->orWhere('user_name', $request->target_track_id)
                ->where('is_deleted', false)
                ->first();

            if (!$targetUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Target user not found.',
                ], 404);
            }

            $targetUserId = $targetUser->id;
        }

        if (!$targetUserId) {
            return response()->json([
                'status' => false,
                'message' => 'Target user ID or Track ID is required.',
            ], 422);
        }

        if ($targetUserId == $orderingUser->id) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot place order for yourself. Use regular purchase.',
            ], 422);
        }

        try {
            $order = $this->purchaseService->purchase(
                $targetUserId,
                $request->product_id,
                $request->quantity,
                $orderingUser->id
            );

            $order->load('user');

            $invoice = Invoice::where('order_id', $order->id)->first();

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully for the user.',
                'data' => $order,
                'invoice' => $invoice,
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order For Someone Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to place order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);
        $user = MlmUser::findOrFail($request->user_id);
        try {
            $query = Order::with(['items.product:id,name,image', 'invoice', 'user'])
                ->where('user_id', $user->id);

            // Filter by status type
            if ($request->filled('type') && $request->type !== 'all') {
                $statusMap = [
                    'completed' => 'COMPLETED',
                    'pending' => 'PENDING',
                    'cancelled' => 'CANCELLED',
                ];
                $status = $statusMap[$request->type] ?? null;
                if ($status) {
                    $query->where('status', $status);
                }
            }

            // Date range filter
            if ($request->filled('from_date')) {
                $query->whereDate('order_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('order_date', '<=', $request->to_date);
            }

            $orders = $query->latest('order_date')
                ->paginate($request->input('per_page', 10));

            return response()->json([
                'success' => true,
                'message' => 'Order history fetched successfully.',
                'data' => $orders,
            ]);
        } catch (\Throwable $e) {
            Log::error('Order History API Error', [
                'user_id' => $request->user_id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch order history.',
            ], 500);
        }
    }

    public function resolveIdentifier(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $user = MlmUser::where('track_id', $request->identifier)
            ->orWhere('user_name', $request->identifier)
            ->where('is_deleted', false)
            ->first(['id', 'track_id', 'user_name', 'first_name', 'last_name', 'email']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }
}
