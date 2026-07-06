<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminBankDetail;
use App\Models\FundRequest;
use App\Models\FundSummary;
use App\Models\FundTransfer;
use App\Http\Resources\MlmUserResource;
use App\Models\MlmUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FundRequestApiController extends Controller
{
     /**
     * Get all fund requests (for admin)
     */
    public function index(Request $request)
    {
        try {
            $query = FundRequest::with(['user', 'bankDetail']);

            // Filter by user
            if ($request->filled('user_id')) {
                $userId = MlmUser::where('id', $request->user_id)->value('id');
                $query->where('user_id', $userId);
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $fundRequests = $query->orderBy('created_at', 'desc')->get();

            $data = $fundRequests->map(fn($fr) => array_merge($fr->toArray(), []));

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Fund requests fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fund requests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve/Reject fund request
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_remark' => 'nullable|string',
        ]);

        try {
            $fundRequest = FundRequest::findOrFail($id);
            $fundRequest->update([
                'status' => $validated['status'],
                'admin_remark' => $validated['admin_remark'] ?? null,
            ]);

            // If approved, add to user's fund summary
            if ($validated['status'] === 'approved') {
                FundSummary::create([
                    'user_id' => $fundRequest->user_id,
                    'username' => $fundRequest->username,
                    'transaction_date' => now(),
                    'type' => 'ADMIN CREDIT',
                    'particular' => 'Fund Request Approved',
                    'remark' => $validated['admin_remark'] ?? 'Fund request approved by admin',
                    'credit' => $fundRequest->amount,
                    'debit' => 0,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Fund request {$validated['status']} successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active bank details for fund request
     */
    public function getBankDetails()
    {
        try {
            $bankDetails = AdminBankDetail::where('is_active', true)
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $bankDetails,
                'message' => 'Bank details fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bank details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit fund request
     */
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'username' => 'required|string',
            'bank_detail_id' => 'required|exists:admin_bank_details,id',
            'payment_mode' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:500',
            'mode_of_payment' => 'required|string',
            'deposit_bank' => 'required|string',
            'transaction_no' => 'required|string',
            'deposit_date' => 'required|date',
            'hash_code' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $userId = MlmUser::where('id', $validated['user_id'])->value('id');

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('hash_code')) {
                $imagePath = $request->file('hash_code')->store('fund-requests', 'public');
            }

            $fundRequest = FundRequest::create([
                'user_id' => $userId,
                'username' => $validated['username'],
                'bank_detail_id' => $validated['bank_detail_id'],
                'payment_mode' => $validated['payment_mode'],
                'amount' => $validated['amount'],
                'remark' => $validated['remark'] ?? null,
                'mode_of_payment' => $validated['mode_of_payment'],
                'deposit_bank' => $validated['deposit_bank'],
                'transaction_no' => $validated['transaction_no'],
                'deposit_date' => $validated['deposit_date'],
                'hash_code_image' => $imagePath,
                'status' => 'pending',
            ]);

            $fundRequest->load('user');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $fundRequest->id,
                    'username' => $fundRequest->username,
                    'bank_detail_id' => $fundRequest->bank_detail_id,
                    'payment_mode' => $fundRequest->payment_mode,
                    'amount' => $fundRequest->amount,
                    'remark' => $fundRequest->remark,
                    'mode_of_payment' => $fundRequest->mode_of_payment,
                    'deposit_bank' => $fundRequest->deposit_bank,
                    'transaction_no' => $fundRequest->transaction_no,
                    'deposit_date' => $fundRequest->deposit_date,
                    'hash_code_image' => $fundRequest->hash_code_image,
                    'status' => $fundRequest->status,
                    'created_at' => $fundRequest->created_at,
                    'updated_at' => $fundRequest->updated_at,
                ],
                'message' => 'Fund request submitted successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit fund request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function withdrawalHistory(Request $request)
    {
        try {
            $userId = MlmUser::where('id', $request->user_id)->value('id');
            $query = FundTransfer::with(['sender', 'receiver'])->where('receiver_id', $userId);

            $fundTransfer = $query->orderBy('created_at', 'desc')->get();

            $data = $fundTransfer->map(fn($ft) => array_merge($ft->toArray(), []));

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Fund Transfer fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch fund Transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}