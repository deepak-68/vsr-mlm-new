# Sanctum API Token Authentication — Implementation Guide

## Overview

Replace the current `user_id`-based API authentication with Laravel Sanctum token-based authentication across the VSR MLM project.

## Current Architecture

| Component | Detail |
|---|---|
| API Auth | Manual `Hash::check()` — no token returned |
| User Identification | `$request->user_id` parameter (44 occurrences across 14 controllers) |
| Auth Guard | `web` (session) only — no `api` guard |
| MlmUser Model | Extends `Model` — not `Authenticatable` |
| Sanctum | **Not installed** |

## Step-by-Step Implementation

### Phase 1: Sanctum Setup & Token Login

#### 1. Install Sanctum
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```
Creates `personal_access_tokens` table and `config/sanctum.php`.

#### 2. Add Migration — `remember_token` to `mlm_users`
```bash
php artisan make:migration add_remember_token_to_mlm_users_table --table=mlm_users
```
```php
// In the up() method:
Schema::table('mlm_users', function (Blueprint $table) {
    $table->rememberToken()->nullable()->after('password');
});
```

#### 3. Update `MlmUser` Model (`app/Models/MlmUser.php`)

Change `extends Model` → `extends Authenticatable`, add `HasApiTokens` trait:

```php
<?php

namespace App\Models;

use App\Models\MlmUserDetail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class MlmUser extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'mlm_users';

    protected $fillable = [
        'user_name', 'track_id', 'first_name', 'last_name',
        'email', 'phone', 'password', 'sponsor_id', 'position_in_sponsor_leg',
        'membership_type', 'desired_membership_type', 'current_package_id',
        'is_active', 'is_verified', 'is_deleted', 'is_defaulter', 'is_payout_active',
        'verification_token', 'verification_expires', 'commission_percentage',
    ];

    protected $hidden = ['password', 'verification_token', 'remember_token'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_deleted' => 'boolean',
        'is_defaulter' => 'boolean',
        'is_payout_active' => 'boolean',
        'verification_expires' => 'datetime',
    ];

    // ... all existing relationships and methods remain unchanged ...
}
```

#### 4. Configure `config/auth.php`

Add `api` guard and `mlm_users` provider:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'mlm_users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', App\Models\User::class),
    ],
    'mlm_users' => [
        'driver' => 'eloquent',
        'model' => App\Models\MlmUser::class,
    ],
],
```

#### 5. Update `AuthController@login` (`app/Http/Controllers/Api/Auth/AuthController.php`)

Replace the commented-out token generation with active code. After successful password verification:

```php
// Delete previous tokens for single-session-per-user
$mlmUser->tokens()->delete();

$token = $mlmUser->createToken('API Token')->plainTextToken;

return response()->json([
    'success' => true,
    'message' => 'Login successful.',
    'token'   => $token,
    'user'    => [
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
    ],
]);
```

#### 6. Add `logout()` and `me()` to `AuthController`

```php
public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'success' => true,
        'message' => 'Logged out successfully.',
    ]);
}

public function me(Request $request)
{
    $user = $request->user()->load(['detail:id,user_id,profile_image']);
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
        ],
    ]);
}
```

#### Phase 1 Verification
- POST `/api/login` with valid credentials → returns `token` + `user`
- POST `/api/logout` with `Authorization: Bearer {token}` → deletes token → 200
- GET `/api/me` with `Authorization: Bearer {token}` → returns user data
- Old `user_id` parameter still works on unprotected routes (backward compatible)

---

### Phase 2: Protect Routes & Update Controllers

#### 7. Update `routes/api.php`

Group all protected routes under `auth:sanctum` middleware:

```php
use Illuminate\Support\Facades\Route;

// === PUBLIC ROUTES ===
Route::get('/ping', function () {
    return response()->json(['status' => 'API working']);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/user-register', [UserRegisterController::class, 'register']);

// Public content pages
Route::get('/home', [HomeApiController::class, 'index']);
Route::get('/about-us', [AboutApiController::class, 'index']);
Route::get('/our-commitment', [CommitmentApiController::class, 'index']);
Route::get('/our-team', [TeamApiController::class, 'index']);
Route::get('/privacy-policy', [PrivacyApiController::class, 'index']);
Route::get('/terms-conditions', [TermsApiController::class, 'index']);
Route::get('/accessibility', [AccessibilityApiController::class, 'index']);
Route::get('/shipping-policy', [ShippingPolicyApiController::class, 'index']);
Route::get('/disclaimer', [DisclaimerApiController::class, 'index']);
Route::get('/cancel-policy', [CancellationApiController::class, 'index']);
Route::get('/grievance-redressal', [GrievanceApiController::class, 'index']);
Route::get('/ndis', [NdisApiController::class, 'index']);
Route::get('/aged-care', [AgedcareApiController::class, 'index']);
Route::get('/blogs', [BlogsApiController::class, 'index']);
Route::get('/products', [ProductApiController::class, 'index']);
Route::get('/niisq', [NiisqApiController::class, 'index']);
Route::get('/dva', [DvaApiController::class, 'index']);
Route::get('/client-resource', [ClientResourcesApiController::class, 'index']);
Route::get('/staff-resource', [StaffResourcesApiController::class, 'index']);
Route::get('/faqs', [FaqApiController::class, 'index']);
Route::get('/jobs', [CareerApiController::class, 'index']);
Route::get('/jobs/{slug}', [CareerApiController::class, 'show']);
Route::get('/home-service', [HomeServiceApiController::class, 'index']);
Route::get('/community-participation-service', [CommunityParticipationApiController::class, 'index']);
Route::get('/support-independent-service', [supportIndependentApiController::class, 'index']);
Route::get('/care-coordination-service', [careCoordinationApiController::class, 'index']);
Route::get('/community-nursing-service', [communityNursingApiController::class, 'index']);
Route::get('/allied-health-service', [alliedHealthApiController::class, 'index']);
Route::get('/plan-management-service', [planManagementApiController::class, 'index']);
Route::get('/support-coordination-service', [supportCoordinationApiController::class, 'index']);
Route::get('/system-setting', [SystemApiController::class, 'index']);

// === AUTHENTICATED ROUTES ===
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // MLM / Referral
    Route::get('/referrals', [MLMApiController::class, 'getReferrals']);
    Route::get('/referrals/profile/{userId}', [MLMApiController::class, 'getReferralProfile']);
    Route::get('/holding-tank', [MLMApiController::class, 'getHoldingTank']);
    Route::post('/holding-tank/place', [MLMApiController::class, 'placeUser']);
    Route::get('/downline', [MLMApiController::class, 'getReferralDownline']);
    Route::get('/team/genealogy', [MLMApiController::class, 'getTeamGenealogy']);
    Route::get('/team/downline', [MLMApiController::class, 'getTeamDownline']);
    Route::get('/team/user-profile/{userId}', [MLMApiController::class, 'getUserProfile']);
    Route::get('/team/user-downline/{userId}', [MLMApiController::class, 'getUserDownline']);

    // Financial
    Route::get('/admin-bank-details', [AdminBankApiController::class, 'index']);
    Route::get('/admin-bank-details/{id}', [AdminBankApiController::class, 'show']);
    Route::get('/fund-summary', [FundSummaryApiController::class, 'index']);
    Route::get('/fund-request/bank-details', [FundRequestApiController::class, 'getBankDetails']);
    Route::post('/fund-request/submit', [FundRequestApiController::class, 'submit']);
    Route::get('/fund-requests', [FundRequestApiController::class, 'index']);
    Route::put('/fund-requests/{id}/status', [FundRequestApiController::class, 'updateStatus']);
    Route::get('/withdrawal-history', [FundRequestApiController::class, 'withdrawalHistory']);
    Route::post('/fund-transfer/transfer', [FundTransferApiController::class, 'transfer']);
    Route::get('/fund-transfer/sent', [FundTransferApiController::class, 'getSentTransfers']);
    Route::get('/fund-transfer/received', [FundTransferApiController::class, 'getReceivedTransfers']);
    Route::get('/fund-transfer/wallet-balance', [FundTransferApiController::class, 'getWalletBalance']);

    // KYC
    Route::get('/kyc', [KycController::class, 'index']);
    Route::get('/kyc/status', [KycController::class, 'kycStatus']);
    Route::post('/kyc/submit', [KycController::class, 'submit']);

    // Grievance / Tickets
    Route::post('raise-ticket', [GrievanceController::class, 'raiseTicket']);
    Route::post('reply-ticket', [GrievanceController::class, 'replyTicket']);
    Route::get('ticket-messages/{id}', [GrievanceController::class, 'getMessages']);
    Route::put('ticket-status/{id}', [GrievanceController::class, 'changeStatus']);
    Route::get('my-tickets', [GrievanceController::class, 'myTickets']);
    Route::get('outbox', [GrievanceController::class, 'outbox']);

    // Orders
    Route::post('purchase', [OrderController::class, 'purchase']);
    Route::get('order-history', [OrderController::class, 'history']);

    // Wallet
    Route::get('direct-income', [WalletController::class, 'directIncome']);
    Route::get('matching-income', [WalletController::class, 'matchingIncome']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('/change-password', [ProfileController::class, 'changePassword']);
    Route::post('profile/update-image', [ProfileController::class, 'updateImage']);
});
```

#### 8. Update Controllers — Replace `$request->user_id`

In each of the 14 controllers, replace:

| Before | After |
|---|---|
| `$request->user_id` | `$request->user()->id` |
| `MlmUser::find($request->user_id)` | `$request->user()` |

**Example changes:**

**`ProfileController.php`:**
```php
// Before:
$user = MlmUser::with(['detail'])->findOrFail($request->user_id);
// After:
$user = $request->user()->loadMissing('detail');
```

**`MLMApiController.php`:**
```php
// Before:
$user = MlmUser::find($request->user_id);
// After:
$user = $request->user();
```

**`OrderController.php`:**
```php
// Before:
'user_id' => $request->user_id,
// After:
'user_id' => $request->user()->id,
```

---

### Phase 3: Frontend Integration (Separate Laravel App)

#### Store & Send Token

```php
// Login — store token in session
$response = Http::post('https://api.vsr-mlm.com/api/login', [
    'username' => $request->username,
    'password' => $request->password,
]);

if ($response->successful()) {
    session(['api_token' => $response->json('token')]);
}

// Subsequent requests — attach Bearer token
Http::withToken(session('api_token'))
    ->post('https://api.vsr-mlm.com/api/profile/update', $data);
```

#### Axios (if using SPA frontend)

```js
// After login:
localStorage.setItem('api_token', response.data.token);
axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

// API service wrapper
const api = axios.create({
    baseURL: '/api',
    headers: { Authorization: `Bearer ${localStorage.getItem('api_token')}` },
});
```

---

## Configuration Reference

### `config/sanctum.php` Key Settings

```php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:3000')),
    'guard' => ['web'],
    'expiration' => null,         // null = permanent tokens
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

Set token expiry if desired:
```env
SANCTUM_TOKEN_EXPIRATION=1440    # 24 hours
```

---

## Testing

```bash
# Login
curl -X POST http://127.0.0.1:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"secret"}'

# Use returned token
curl http://127.0.0.1:8001/api/me \
  -H "Authorization: Bearer 1|abc123..."

# Logout
curl -X POST http://127.0.0.1:8001/api/logout \
  -H "Authorization: Bearer 1|abc123..."
```

---

## Rollback Plan

| Scenario | Action |
|---|---|
| Token auth fails | Comment out `auth:sanctum` middleware, keep `$request->user_id` fallback |
| MlmUser model breaks | Revert `extends Authenticatable` → `extends Model` |
| Migration issues | `php artisan migrate:rollback` on `personal_access_tokens` and `remember_token` |

---

## File Change Summary

| File | Action |
|---|---|
| `composer.json` | Add `laravel/sanctum` |
| `config/auth.php` | Add `api` guard + `mlm_users` provider |
| `config/sanctum.php` | Created by vendor:publish |
| `app/Models/MlmUser.php` | Change base class, add `HasApiTokens` |
| `app/Http/Controllers/Api/Auth/AuthController.php` | Activate token creation, add `logout()`, `me()` |
| `routes/api.php` | Group protected routes under `auth:sanctum` |
| 14 controller files | Replace `$request->user_id` → `$request->user()->id` |
| New migration | `add_remember_token_to_mlm_users_table` |
