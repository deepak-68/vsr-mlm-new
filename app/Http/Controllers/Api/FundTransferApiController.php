<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MlmUserResource;
use App\Models\FundTransfer;
use App\Models\FundSummary;
use App\Models\MlmUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class FundTransferApiController extends Controller
{
    /**
     * Transfer funds to another user
     */
    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'sender_id' => 'required',
            'receiver_username' => 'required|string|exists:mlm_users,user_name',
            'amount' => 'required|numeric|min:1',
            'transaction_password' => 'required|string',
        ]);

        try {
            $senderId = MlmUser::where('id', $validated['sender_id'])->value('id');
            $sender = MlmUser::findOrFail($senderId);
            $receiver = MlmUser::where('user_name', $validated['receiver_username'])->first();

            // Verify transaction password
            if (!Hash::check($validated['transaction_password'], $sender->transaction_password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction password'
                ], 422);
            }

            // Check if sender has sufficient balance
            $senderBalance = FundSummary::where('user_id', $sender->id)
                ->sum(DB::raw('credit - debit'));

            if ($senderBalance < $validated['amount']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance'
                ], 422);
            }

            // Create fund transfer record
            $transfer = FundTransfer::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'sender_username' => $sender->user_name,
                'receiver_username' => $receiver->user_name,
                'amount' => $validated['amount'],
                'remark' => $validated['remark'] ?? null,
                'transaction_password' => $validated['transaction_password'],
                'status' => 'completed',
            ]);

            // Deduct from sender
            FundSummary::create([
                'user_id' => $sender->id,
                'username' => $sender->user_name,
                'transaction_date' => now(),
                'type' => 'Debit Transfer',
                'particular' => 'Fund Transfer to ' . $receiver->user_name,
                'remark' => 'Transferred ' . $validated['amount'] . ' to ' . $receiver->user_name,
                'credit' => 0,
                'debit' => $validated['amount'],
            ]);

            // Add to receiver
            FundSummary::create([
                'user_id' => $receiver->id,
                'username' => $receiver->user_name,
                'transaction_date' => now(),
                'type' => 'Credit Transfer',
                'particular' => 'Fund Received from ' . $sender->user_name,
                'remark' => 'Received ' . $validated['amount'] . ' from ' . $sender->user_name,
                'credit' => $validated['amount'],
                'debit' => 0,
            ]);

            $transfer->load(['sender', 'receiver']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transfer->id,
                    'sender_username' => $transfer->sender_username,
                    'receiver_username' => $transfer->receiver_username,
                    'amount' => $transfer->amount,
                    'remark' => $transfer->remark,
                    'status' => $transfer->status,
                    'created_at' => $transfer->created_at,
                    'updated_at' => $transfer->updated_at,
                ],
                'message' => 'Fund transferred successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sender's transfer history
     */
    public function getSentTransfers(Request $request)
    {
        try {
            $userId = MlmUser::where('id', $request->user_id)->value('id');
            $query = FundTransfer::with(['sender', 'receiver'])->where('sender_id', $userId);

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transfers = $query->orderBy('created_at', 'desc')->get();

            $data = $transfers->map(fn($t) => array_merge($t->toArray(), []));

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Transfers fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transfers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get receiver's transfer history
     */
    public function getReceivedTransfers(Request $request)
    {
        try {
            $userId = MlmUser::where('id', $request->user_id)->value('id');
            $query = FundTransfer::with(['sender', 'receiver'])->where('receiver_id', $userId);

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $transfers = $query->orderBy('created_at', 'desc')->get();

            $data = $transfers->map(fn($t) => array_merge($t->toArray(), []));

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Received transfers fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch received transfers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's fund wallet balance
     */
    public function getWalletBalance(Request $request)
    {
        try {
            $balance = FundSummary::where('user_id', $request->user_id)
                ->sum(DB::raw('credit - debit'));

            return response()->json([
                'success' => true,
                'balance' => $balance,
                'message' => 'Balance fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}