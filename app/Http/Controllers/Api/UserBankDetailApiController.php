<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserBankDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserBankDetailApiController extends Controller
{
    public function show(Request $request)
    {
        try {
            $userId = $request->user_id ?? $request->input('user_id');

            $bankDetail = UserBankDetail::where('user_id', $userId)->first();

            return response()->json([
                'success' => true,
                'data' => $bankDetail,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bank details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($userId)
    {
        try {
            $bankDetail = UserBankDetail::where('user_id', $userId)->first();

            if (!$bankDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'No bank details found to delete',
                ], 404);
            }

            if ($bankDetail->bank_attachment) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($bankDetail->bank_attachment);
            }

            $bankDetail->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bank details deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete bank details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:mlm_users,id',
            'account_holder_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|max:50',
            'bank_attachment' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $data = [
                'user_id' => $validated['user_id'],
                'account_holder_name' => $validated['account_holder_name'],
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['account_number'],
                'ifsc_code' => $validated['ifsc_code'],
            ];

            if ($request->hasFile('bank_attachment')) {
                $data['bank_attachment'] = $request->file('bank_attachment')
                    ->store('user-bank-attachments', 'public');
            }

            $bankDetail = UserBankDetail::updateOrCreate(
                ['user_id' => $validated['user_id']],
                $data
            );

            return response()->json([
                'success' => true,
                'data' => $bankDetail,
                'message' => 'Bank details saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save bank details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
