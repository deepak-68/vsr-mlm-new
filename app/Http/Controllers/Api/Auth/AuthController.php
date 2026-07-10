<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\MlmUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            // 'cf_turnstile_response' => 'nullable|string',
        ]);

        /**
         * Optional: Verify Cloudflare Turnstile
         * Skip if mobile app or disabled
         */
        // if ($request->filled('cf_turnstile_response')) {

        //     $response = Http::asForm()->post(
        //         'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        //         [
        //             'secret'   => env('TURNSTILE_SECRETKEY'),
        //             'response' => $request->cf_turnstile_response,
        //             'remoteip' => $request->ip(),
        //         ]
        //     );

        //     if (!($response->json()['success'] ?? false)) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'CAPTCHA verification failed.'
        //         ], 422);
        //     }
        // }

        $mlmUser = MlmUser::with([
                'detail'
            ])
            ->where(function ($query) use ($request) {
                $query->where('user_name', $request->username)
                    ->orWhere('email', $request->username)
                    ->orWhere('phone', $request->username);
            })
            ->where('is_deleted', 0)
            ->where('is_active', 1)
            ->first();

            if ($mlmUser) {
                $mlmUser->profile_image = !empty($mlmUser->detail?->profile_image)
                    ? asset('storage/' . $mlmUser->detail->profile_image)
                    : null;
            }



        // return response()->json($user);


        if (!$mlmUser || !Hash::check($request->password, $mlmUser->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        // Eloquent model required for Sanctum token
        // $mlmUser = \App\Models\MlmUser::find($user->id);

        // Delete previous token if needed
        $mlmUser->tokens()->delete();

        $token = $mlmUser->createToken('Frontend')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $mlmUser->id,
                'track_id' => $mlmUser->track_id,
                'user_name' => $mlmUser->user_name,
                'first_name' => $mlmUser->first_name,
                'last_name' => $mlmUser->last_name,
                'email' => $mlmUser->email,
                'phone' => $mlmUser->phone,
                'profile_image' => $mlmUser->profile_image,
                'membership_type' => $mlmUser->membership_type,
                'is_payout_active' => $mlmUser->is_payout_active,
                'created_at' => $mlmUser->created_at,
                'address' => $mlmUser->address_line_1,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // return response()->json($request->all());
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }


    public function me(Request $request)
    {
        $user = $request->user()->load(['detail']);
        $user->profile_image = !empty($user->detail?->profile_image)
            ? asset('storage/' . $user->detail->profile_image)
            : null;

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'track_id' => $user->track_id,
                'user_name' => $user->user_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_image' => $user->profile_image,
                'membership_type' => $user->membership_type,
                'is_payout_active' => $user->is_payout_active,
                'created_at' => $user->created_at,
                'address' => $user->address_line_1,

            ],
        ]);
    }
}
