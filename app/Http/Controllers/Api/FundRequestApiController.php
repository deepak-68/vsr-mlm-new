<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminBankDetail;
use App\Models\FundRequest;
use App\Models\FundSummary;
use App\Models\FundTransfer;
use App\Http\Resources\MlmUserResource;
use App\Models\MlmUser;
use App\Services\MailNotificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FundRequestApiController extends Controller
{
     /**
     * Get all fund requests (for admin)
     */
    public function index(Request $request)
    {
        try {
            $query = FundRequest::with(['user', 'bankDetail', 'userBankDetail']);

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
                $isWithdrawal = is_null($fundRequest->bank_detail_id) || $fundRequest->payment_mode === 'Withdrawal';

                if ($isWithdrawal) {
                    // Withdrawal approval = debit from user
                    FundSummary::create([
                        'user_id' => $fundRequest->user_id,
                        'username' => $fundRequest->username,
                        'transaction_date' => now(),
                        'type' => 'ADMIN DEBIT',
                        'particular' => 'Withdrawal Approved',
                        'remark' => $validated['admin_remark'] ?? 'Withdrawal request approved by admin',
                        'credit' => 0,
                        'debit' => $fundRequest->amount,
                    ]);
                } else {
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
            }

            // In-app notification + email for withdrawal status
            try {
                $user = $fundRequest->user;
                if ($user) {
                    app(NotificationService::class)->createWithdrawalNotification(
                        $user->id, $validated['status'], $validated['admin_remark'] ?? ''
                    );
                    app(MailNotificationService::class)->sendWithdrawalUpdate(
                        $user, $fundRequest->amount, $validated['status']
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Withdrawal notification failed: ' . $e->getMessage());
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
        $type = $request->input('type', 'deposit');

        Log::info('FundRequestApiController@submit called', [
            'type' => $type,
            'all_input' => $request->except(['hash_code']),
            'has_file' => $request->hasFile('hash_code'),
        ]);

        $rules = [
            'user_id' => 'required|exists:mlm_users,id',
            // 'username' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'remark' => 'nullable|string|max:500',
            'mode_of_payment' => 'required|string',
        ];

        if ($type === 'withdrawal') {
            $rules['user_bank_id'] = 'required|exists:user_bank_details,id';
        } else {
            $rules['bank_detail_id'] = 'required|exists:admin_bank_details,id';
            $rules['payment_mode'] = 'required|string';
            $rules['deposit_bank'] = 'required|string';
            $rules['transaction_no'] = 'required|string';
            $rules['deposit_date'] = 'required|date';
            $rules['hash_code'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $validated = $request->validate($rules);

        Log::info('FundRequestApiController validation passed', ['validated' => $validated]);

        try {
            $userId = MlmUser::where('id', $validated['user_id'])->value('id');

            Log::info('FundRequestApiController resolved userId', ['user_id' => $userId]);

            if ($type === 'withdrawal') {
                $fundRequest = FundRequest::create([
                    'user_id' => $userId,
                    'username' => $request->user()->user_name,
                    'user_bank_detail_id' => $validated['user_bank_id'],
                    'payment_mode' => 'Withdrawal',
                    'amount' => $validated['amount'],
                    'remark' => $validated['remark'] ?? 'Withdrawal request',
                    'mode_of_payment' => $validated['mode_of_payment'],
                    'deposit_bank' => '',
                    'transaction_no' => '',
                    'deposit_date' => now(),
                    'hash_code_image' => null,
                    'status' => 'pending',
                ]);
            } else {
                $imagePath = null;
                if ($request->hasFile('hash_code')) {
                    $imagePath = $request->file('hash_code')->store('fund-requests', 'public');
                }

                $userBankDetail = \App\Models\UserBankDetail::where('user_id', $userId)->first();

                $fundRequest = FundRequest::create([
                    'user_id' => $userId,
                    'username' => $validated['username'],
                    'bank_detail_id' => $validated['bank_detail_id'],
                    'user_bank_detail_id' => $userBankDetail?->id,
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
            }

            Log::info('FundRequestApiController created fund request', [
                'id' => $fundRequest->id,
                'type' => $type
            ]);

            $fundRequest->load('user');

            $responseData = $fundRequest->toArray();
            $responseData['type'] = $type;

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => $type === 'withdrawal' ? 'Withdrawal request submitted successfully' : 'Fund request submitted successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('FundRequestApiController submit failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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