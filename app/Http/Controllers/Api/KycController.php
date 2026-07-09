<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MlmUserResource;
use App\Models\Kyc;
use App\Models\MlmUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KycController extends Controller
{
    public function index(Request $request)
    {
        
        return response()->json(['success' => true, 'message' => 'KYC details retrieved successfully', ]);
    }

    // public function submit(Request $request, )
    // {

    //     // return response()->json($request->all());
    //     $request->validate([
    //         'pan_number' => 'required|string|max:10',
    //         'aadhaar_number' => 'required|string|max:12',
    //         'pan_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    //         'aadhaar_front_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    //         'aadhaar_back_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    //         'bank_document_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    //     ]);

    //     $kyc = Kyc::updateOrCreate(
    //         [
    //             'user_id' => $request->user_id,
    //         ],
    //         [
    //             'pan_number' => $request->pan_number,
    //             'aadhaar_number' => $request->aadhaar_number,
    //             'pan_image' => $request->file('pan_image')->store('kyc/pan', 'public'),

    //             'aadhaar_front_image' => $request->file('aadhaar_front_image')->store('kyc/aadhaar/front', 'public'),

    //             'aadhaar_back_image' => $request->file('aadhaar_back_image')->store('kyc/aadhaar/back', 'public'),

    //             'bank_document_image' => $request->file('bank_document_image')->store('kyc/bank', 'public'),

    //             'status' => 'pending',
    //             'reject_reason' => null,
    //         ]
    //     );

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'KYC submitted successfully',
    //         'data' => $kyc
    //     ]);
    // }

    public function submit(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'pan_number' => 'required|string|max:10',
            'aadhaar_number' => 'required|string|max:12',
            'pan_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'aadhaar_front_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'aadhaar_back_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'bank_document_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

            $userId = MlmUser::where('id', $request->user_id)->value('id');
        $kyc = Kyc::firstOrNew([
            'user_id' => $userId
        ]);

        $kyc->pan_number = $request->pan_number;
        $kyc->aadhaar_number = $request->aadhaar_number;
        $kyc->status = 'pending';
        $kyc->reject_reason = null;

        // PAN Image
        if ($request->hasFile('pan_image')) {
            $filename = time() . '_pan.' . $request->file('pan_image')->getClientOriginalExtension();
            $request->file('pan_image')->move(public_path('kyc/pan'), $filename);
            $kyc->pan_image = 'kyc/pan/' . $filename;
        }

        // Aadhaar Front
        if ($request->hasFile('aadhaar_front_image')) {
            $filename = time() . '_aadhaar_front.' . $request->file('aadhaar_front_image')->getClientOriginalExtension();
            $request->file('aadhaar_front_image')->move(public_path('kyc/aadhaar/front'), $filename);
            $kyc->aadhaar_front_image = 'kyc/aadhaar/front/' . $filename;
        }

        // Aadhaar Back
        if ($request->hasFile('aadhaar_back_image')) {
            $filename = time() . '_aadhaar_back.' . $request->file('aadhaar_back_image')->getClientOriginalExtension();
            $request->file('aadhaar_back_image')->move(public_path('kyc/aadhaar/back'), $filename);
            $kyc->aadhaar_back_image = 'kyc/aadhaar/back/' . $filename;
        }

        // Bank Document
        if ($request->hasFile('bank_document_image')) {
            $filename = time() . '_bank.' . $request->file('bank_document_image')->getClientOriginalExtension();
            $request->file('bank_document_image')->move(public_path('kyc/bank'), $filename);
            $kyc->bank_document_image = 'kyc/bank/' . $filename;
        }

        $kyc->save();

        // Update PAN and Aadhaar in user details table
        $userDetail = \App\Models\MlmUserDetail::firstOrNew(['user_id' => $userId]);
        $userDetail->pan_number = $request->pan_number;
        $userDetail->aadhaar_number = $request->aadhaar_number;
        $userDetail->save();

        $kyc->load('user');

        return response()->json([
            'status' => true,
            'message' => 'KYC submitted successfully',
            'data' => [
                'id' => $kyc->id,
                'pan_number' => $kyc->pan_number,
                'aadhaar_number' => $kyc->aadhaar_number,
                'pan_image' => $kyc->pan_image,
                'aadhaar_front_image' => $kyc->aadhaar_front_image,
                'aadhaar_back_image' => $kyc->aadhaar_back_image,
                'bank_document_image' => $kyc->bank_document_image,
                'status' => $kyc->status,
                'reject_reason' => $kyc->reject_reason,
                'created_at' => $kyc->created_at,
                'updated_at' => $kyc->updated_at,
            ]
        ]);
    }


    public function kycStatus(Request $request)
    {
        try {
            $userId = MlmUser::where('id', $request->user_id)->value('id');

            // Fetch KYC record with all fields
            $kyc = Kyc::where('user_id', $userId)->latest()->first();

            // Fetch PAN from user details for autofill
            $userDetail = \App\Models\MlmUserDetail::where('user_id', $userId)->first();

            $data = $kyc ? [
                'id' => $kyc->id,
                'status' => $kyc->status,
                'user_id' => $kyc->user_id,
                'pan_number' => $kyc->pan_number,
                'aadhaar_number' => $kyc->aadhaar_number,
                'pan_image' => $kyc->pan_image ? asset($kyc->pan_image) : null,
                'aadhaar_front_image' => $kyc->aadhaar_front_image ? asset($kyc->aadhaar_front_image) : null,
                'aadhaar_back_image' => $kyc->aadhaar_back_image ? asset($kyc->aadhaar_back_image) : null,
                'bank_document_image' => $kyc->bank_document_image ? asset($kyc->bank_document_image) : null,
                'reject_reason' => $kyc->reject_reason,
                'created_at' => $kyc->created_at,
                'updated_at' => $kyc->updated_at,
            ] : null;

            // If no KYC record, provide PAN from registration for autofill
            if (!$data && $userDetail) {
                $data = [
                    'id' => null,
                    'status' => null,
                    'user_id' => $userId,
                    'pan_number' => $userDetail->pan_number,
                    'aadhaar_number' => '',
                    'pan_image' => null,
                    'aadhaar_front_image' => null,
                    'aadhaar_back_image' => null,
                    'bank_document_image' => null,
                    'reject_reason' => null,
                    'created_at' => null,
                    'updated_at' => null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            Log::error('KYC Status Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching KYC status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
