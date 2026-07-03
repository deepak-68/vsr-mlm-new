# MLM Phase 2 Implementation Plan

**Project:** MLM Binary Business Management System  
**Date:** July 1, 2026  
**Total Estimated Effort:** 10-12 Weeks  

---

## Key Business Rule Changes

| Rule | Current (Phase 1) | New (Phase 2) |
|------|--------------------|---------------|
| CC Value | 1 CC = ₹60 | **1 CC = ₹1** |
| Activation | Email link | **Purchase 2+ products** |
| Activation trigger | Click verification link | **Auto-activate after purchase** |
| Rank basis | Not implemented | **Self Income (CC)** |
| Reward progress | Not implemented | **Resets to zero after each rank** |
| Registration placement | Holding Tank → Admin places | **User selects Left/Right at signup** |
| Minimum purchase | None | **2 products (mandatory)** |

---

## Timeline Overview

```
Phase 1: Foundation & Database      → Weeks 1-2
Phase 2: Core Business Logic         → Weeks 3-4
Phase 3: User Dashboard & KPIs      → Weeks 5-6
Phase 4: Income & Wallet Systems     → Weeks 6-7
Phase 5: Purchase & Order Modules    → Weeks 7-8
Phase 6: Notification System         → Week 8
Phase 7: Grievance & Support         → Weeks 8-9
Phase 8: Reports & Admin             → Weeks 9-10
Phase 9: Integration Testing         → Weeks 10-11
Phase 10: Deployment                 → Week 12
```

---

# Phase 1: Foundation & Database (Weeks 1-2)

## 1.1 CC Value Migration

**Change:** 1 CC = ₹1 (from ₹60)

### Files to Modify
| File | Change |
|------|--------|
| `app/Models/CcPointSetting.php` | Update `conversion_rate` default to 1.00 |
| `app/Models/CCSetting.php` | Update default value to 1.00 |
| `database/seeders/DatabaseSeeder.php` | Seed new CC rate |
| `app/Services/PurchaseService.php` | Update CC constant: `CC_PER_PRODUCT = 180` (unchanged), but CC→INR conversion = 1:1 |
| `app/Services/PayoutService.php` | Update `ccToCurrency()` to use ₨1 per CC |

### Database Migrations
- Update `cc_point_settings.conversion_rate` default to `1.00`
- Update `cc_settings.value` default to `1.00`

---

## 1.2 New Tables

### `ranks`
```php
Schema::create('ranks', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50);           // "1 Star", "Bronze", etc.
    $table->string('slug', 50)->unique(); // "1-star", "bronze"
    $table->decimal('required_self_cc', 14, 2); // Self Income CC required
    $table->tinyInteger('level')->unique();      // Sort order 1-6
    $table->string('badge_icon', 255)->nullable();
    $table->string('reward_title', 255)->nullable();
    $table->text('reward_description')->nullable();
    $table->string('reward_image', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

### `user_ranks`
```php
Schema::create('user_ranks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('mlm_users')->cascadeOnDelete();
    $table->foreignId('rank_id')->constrained('ranks');
    $table->timestamp('earned_at');
    $table->boolean('is_current')->default(false);
    $table->enum('reward_status', ['pending', 'fulfilled'])->default('pending');
    $table->timestamp('reward_delivered_at')->nullable();
    $table->timestamps();
});
```

### `callback_requests`
```php
Schema::create('callback_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('mlm_users')->cascadeOnDelete();
    $table->date('preferred_date');
    $table->time('preferred_time');
    $table->text('issue_summary');
    $table->enum('status', ['pending', 'called', 'resolved', 'cancelled'])->default('pending');
    $table->text('admin_notes')->nullable();
    $table->timestamps();
});
```

### `notifications`
```php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type');                   // Notification class
    $table->morphs('notifiable');
    $table->text('data');                     // JSON payload
    $table->timestamp('read_at')->nullable();
    $table->string('title', 255)->nullable(); // Quick display title
    $table->string('icon', 50)->nullable();   // Icon class
    $table->string('action_url', 255)->nullable(); // Click-through URL
    $table->timestamps();
});
```

### `reward_progress`
```php
Schema::create('reward_progress', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('mlm_users')->cascadeOnDelete();
    $table->foreignId('rank_id')->constrained('ranks');
    $table->decimal('progress_cc', 14, 2)->default(0); // Current progress
    $table->decimal('target_cc', 14, 2);                // Required CC for this rank
    $table->timestamp('last_reset_at')->nullable();     // When reward was issued
    $table->timestamps();
});
```

### `income_logs`
```php
Schema::create('income_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('mlm_users')->cascadeOnDelete();
    $table->foreignId('from_user_id')->nullable()->constrained('mlm_users');
    $table->string('income_type', 50);       // retail, direct, matching, level, reward_tour, repurchase, rank
    $table->decimal('cc_amount', 14, 2);
    $table->decimal('currency_amount', 14, 2);
    $table->decimal('balance_after', 14, 2);
    $table->string('reference_type', 50)->nullable(); // order, rank, reward
    $table->unsignedBigInteger('reference_id')->nullable();
    $table->string('order_number', 50)->nullable();
    $table->text('remarks')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'income_type']);
    $table->index('created_at');
});
```

---

## 1.3 Modified Tables — Add Columns

### `mlm_users`
| Column | Type | Purpose |
|--------|------|---------|
| `current_rank_id` | FK→ranks.id nullable | Current rank |
| `activated_at` | timestamp nullable | When activated by 2nd product |
| `accepted_terms_at` | timestamp nullable | When T&C accepted |
| `accepted_privacy_at` | timestamp nullable | When privacy policy accepted |
| `registration_ip` | string(45) nullable | IP at registration |

### `order_items`
| Column | Type | Purpose |
|--------|------|---------|
| `cc_per_unit` | decimal(10,2) default 180.00 | Snapshot of CC per unit |
| `invoice_number` | string(50) nullable | Linked invoice reference |

### `orders`
| Column | Type | Purpose |
|--------|------|---------|
| `invoice_number` | string(50) unique nullable | Generated invoice number |
| `ordered_by_user_id` | FK→mlm_users.id nullable | If admin/other user placed on behalf |
| `invoice_generated_at` | timestamp nullable | |

### `grivances`
| Column | Type | Purpose |
|--------|------|---------|
| `resolved_at` | timestamp nullable | When resolved |
| `callback_request_id` | FK→callback_requests.id nullable | Linked callback |

---

## 1.4 Seed Data

### `ranks` seeder
| Level | Name | Required Self CC | Reward |
|-------|------|-----------------|--------|
| 1 | 1 Star | 18,000 CC | Company Kit + Catalogue + Uniform |
| 2 | Bronze | 45,000 CC | Smartwatch |
| 3 | Silver | 90,000 CC | LED TV + Shimla Tour |
| 4 | Gold | 3,00,000 CC | Bike |
| 5 | Ruby | 7,20,000 CC | Car / Car Fund ₹7,00,000 |
| 6 | Crown | 81,00,000 CC | House worth ₹25,00,000 |

---

# Phase 2: Core Business Logic (Weeks 3-4)

## 2.1 Activation Service

**New File:** `app/Services/ActivationService.php`

```php
class ActivationService
{
    const MIN_PRODUCTS_FOR_ACTIVATION = 2;

    public function checkAndActivate(MlmUser $user): bool
    {
        // Count total products from all completed orders
        $totalProducts = OrderItem::whereHas('order', fn($q) =>
            $q->where('user_id', $user->id)
              ->whereIn('status', ['COMPLETED', 'DELIVERED', 'CONFIRMED'])
        )->sum('quantity');

        if ($totalProducts >= self::MIN_PRODUCTS_FOR_ACTIVATION && !$user->is_active) {
            $user->update([
                'is_active' => true,
                'activated_at' => now(),
            ]);
            // Dispatch notifications
            event(new UserActivated($user));
            return true;
        }
        return false;
    }
}
```

### Files to Modify
| File | Change |
|------|--------|
| `app/Services/PurchaseService.php` | Call `ActivationService@checkAndActivate` after order completion |
| `app/Http/Controllers/MLM/MLMOrderController.php` | Call activation check |
| `app/Http/Controllers/Api/OrderController.php` | Call activation check |

---

## 2.2 Rank & Reward Service

**New File:** `app/Services/RankService.php`

### Core Methods
```php
class RankService
{
    /**
     * Evaluate rank based on SELF INCOME CC only.
     * Find highest rank the user's self_income_cc qualifies for.
     * If higher rank found: award it, reset reward progress.
     */
    public function evaluate(int $userId): ?UserRank;

    /**
     * Get user's current rank and progress to next.
     */
    public function getProgress(int $userId): array;

    /**
     * Reset reward progress after rank achievement.
     */
    private function resetRewardProgress(int $userId, Rank $achievedRank): void;

    /**
     * Calculate Self Income CC (sum of all income credited to user).
     */
    public function calculateSelfIncomeCC(int $userId): float;
}
```

### Self Income CC Definition
Self Income CC = sum of ALL income credited to the user across all income types:
- Retail Income
- Direct Income
- Matching Income
- Level Income
- Reward & Tour Income
- Repurchase Income
- Rank Income

**Data Source:** `income_logs` table (sum of `cc_amount` where `user_id = X`)

### Files to Create
| File | Purpose |
|------|---------|
| `app/Services/RankService.php` | Rank evaluation logic |
| `app/Models/Rank.php` | Rank model |
| `app/Models/UserRank.php` | UserRank model |
| `app/Models/RewardProgress.php` | Reward progress tracking |

### Reward Reset Logic
```
1. User reaches 18,000 CC Self Income → Rank: 1 Star
2. Reward issued (Company Kit + Catalogue + Uniform)
3. Reward progress resets to 0
4. Next target: 45,000 CC (Bronze) — starting fresh from 0
```

**Note:** Rank qualification progress is **NOT** reset — only reward progress resets. User keeps their rank permanently once earned. The **reward** for the next rank requires fresh CC accumulation after the previous reward was issued.

---

## 2.3 Income Calculation Service

**New File:** `app/Services/IncomeService.php`

### Income Types
| Type | Description | Calculation Basis |
|------|-------------|-------------------|
| Retail Income | Profit from direct sales | Product price - wholesale price |
| Direct Income | Sponsor commission | % of purchase CC |
| Matching Income | Binary pair match | Min(left_cc, right_cc) × rate |
| Level Income | Generation levels | % of downline CC (multi-level) |
| Reward & Tour Income | Rank rewards | Fixed CC per rank achievement |
| Repurchase Income | Repeat purchase bonus | % of repurchase CC |
| Rank Income | Rank bonus | Fixed CC per rank maintained |

### Core Method
```php
public function creditIncome(
    MlmUser $user,
    string $incomeType,
    float $ccAmount,
    ?MlmUser $fromUser = null,
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?string $orderNumber = null,
    ?string $remarks = null
): IncomeLog;
```

### After each income credit, the service must:
1. Create `income_logs` record
2. Update user's wallet balance via wallet system
3. Create `wallet_transactions` record
4. Check rank qualification (call `RankService@evaluate`)
5. Send notification to user
6. Update dashboard KPI cache

### Files to Create
| File | Purpose |
|------|---------|
| `app/Services/IncomeService.php` | Income processing |
| `app/Models/IncomeLog.php` | IncomeLog model |

---

## 2.4 CC Value Update — System-wide Impact

**Change: 1 CC = ₹1**

### Impact Assessment
| Component | Current | New |
|-----------|---------|-----|
| `CcPointSetting::calculatePriceFromCC()` | cc × 60 | cc × 1 |
| `CcPointSetting::calculateCCFromPrice()` | price / 60 | price / 1 |
| `CCSetting::getActiveRate()` | 60.00 | 1.00 |
| `PayoutConfig::ccToCurrency()` | cc × cc_to_currency_rate | cc × 1 |
| Dashboard payout display | `₹{{ $value }}` | `{{ $value }} CC (₹{{ $value }})` |
| Minimum payout threshold | 800 CC = ₹48,000 | 800 CC = ₹800 |
| Rank thresholds | 18,000 CC | 18,000 CC (same, but ₹18,000 now) |

All CC values remain the same numerically — only the INR equivalent changes.

---

# Phase 3: User Dashboard & KPIs (Weeks 5-6)

## 3.1 Dashboard KPI Service

**New File:** `app/Services/DashboardService.php`

```php
class DashboardService
{
    public function getKpis(int $userId): array
    {
        return [
            'retail_income'     => $this->getIncomeSummary($userId, 'retail'),
            'direct_income'     => $this->getIncomeSummary($userId, 'direct'),
            'matching_income'   => $this->getIncomeSummary($userId, 'matching'),
            'level_income'      => $this->getIncomeSummary($userId, 'level'),
            'reward_tour_income'=> $this->getIncomeSummary($userId, 'reward_tour'),
            'repurchase_income' => $this->getIncomeSummary($userId, 'repurchase'),
            'rank_income'       => $this->getIncomeSummary($userId, 'rank'),
            'rank'              => $this->rankService->getProgress($userId),
        ];
    }

    private function getIncomeSummary(int $userId, string $type): array
    {
        $logs = IncomeLog::where('user_id', $userId)
                        ->where('income_type', $type);
        
        return [
            'current_cc'     => (float) $logs->sum('cc_amount'),
            'equivalent_inr' => (float) $logs->sum('cc_amount'), // 1 CC = ₹1
            'lifetime_total' => (float) $logs->sum('cc_amount'),
        ];
    }
}
```

### Each KPI Card Displays
- **Current Income (CC)** — Total CC earned for this income type
- **Equivalent Amount (₹)** — Same numeric value (1 CC = ₹1)
- **Lifetime Total** — Cumulative total (same as current in simple model)

---

## 3.2 User Dashboard View

**New File:** `resources/views/user/dashboard.blade.php`

### Layout
```
┌─────────────────────────────────────────────────────────┐
│  🏆 Rank: 1 Star                [Progress: ██████░░░░]  │
│  Next: Bronze (18,000/45,000 CC)                        │
├─────────────────────────────────────────────────────────┤
│ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐       │
│ │ Retail  │ │ Direct  │ │Matching │ │ Level   │       │
│ │ Income  │ │ Income  │ │ Income  │ │ Income  │       │
│ │ 0 CC    │ │ 0 CC    │ │ 0 CC    │ │ 0 CC    │       │
│ │ ₹0      │ │ ₹0      │ │ ₹0      │ │ ₹0      │       │
│ └─────────┘ └─────────┘ └─────────┘ └─────────┘       │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐                    │
│ │Reward & │ │Repurch. │ │ Rank    │                    │
│ │ Tour    │ │ Income  │ │ Income  │                    │
│ │ 0 CC    │ │ 0 CC    │ │ 0 CC    │                    │
│ │ ₹0      │ │ ₹0      │ │ ₹0      │                    │
│ └─────────┘ └─────────┘ └─────────┘                    │
├─────────────────────────────────────────────────────────┤
│  Recent Activity / Team Summary / Quick Actions         │
└─────────────────────────────────────────────────────────┘
```

### Files to Create
| File | Purpose |
|------|---------|
| `resources/views/user/dashboard.blade.php` | User dashboard |
| `resources/views/user/layout/master.blade.php` | User panel layout |
| `resources/views/user/partials/rank-badge.blade.php` | Rank badge component |
| `resources/views/user/partials/kpi-card.blade.php` | KPI card component |
| `resources/views/user/partials/rank-progress.blade.php` | Rank progress bar |
| `app/Http/Controllers/User/DashboardController.php` | Dashboard controller |

### Routes to Add
```php
Route::prefix('user')->middleware(['auth:mlm_users'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('user.dashboard');
    Route::get('/api/kpis', [DashboardController::class, 'kpis'])->name('user.kpis');
});
```

---

# Phase 4: Income & Wallet Systems (Weeks 6-7)

## 4.1 Income Log — Referral Income Log Page

**New File:** `resources/views/user/income/referral-income-log.blade.php`

### Table Columns
| Date | Income Type | From User ID | From User Name | Order No. | Purchase CC | Income Credited | Wallet Balance | Remarks |
|------|-------------|--------------|----------------|-----------|-------------|-----------------|----------------|---------|
| 01-07-26 | Direct | USR001 | John Doe | INV-001 | 180 CC | 18 CC | 18 CC | Product purchase commission |

### Files to Create
| File | Purpose |
|------|---------|
| `resources/views/user/income/referral-income-log.blade.php` | Income log view |
| `app/Http/Controllers/User/IncomeController.php` | Income controller |
| `app/Http/Controllers/Api/IncomeController.php` | API endpoint |

### Routes
```php
Route::get('/income-log', [IncomeController::class, 'index'])->name('user.income.log');
Route::get('/api/income-log', [Api\IncomeController::class, 'index']);
```

### Filtering
- By income type (dropdown: All, Direct, Matching, Level, etc.)
- By date range
- Search by user ID/name
- Export to CSV

---

## 4.2 Wallet Balance Display

Enhance wallet balance to show:
- Current CC balance
- Equivalent ₹ amount
- Breakdown by income type

### Files to Modify
| File | Change |
|------|--------|
| `app/Http/Controllers/Api/WalletApiController.php` | Add balance breakdown by income type |
| `resources/views/user/wallet/index.blade.php` | Enhanced wallet view |

---

# Phase 5: Purchase & Order Modules (Weeks 7-8)

## 5.1 Purchase History Page

**New File:** `resources/views/user/orders/purchase-history.blade.php`

### Table Columns
| Invoice No. | Order Date | Product Details | Qty | Total Amount | CC Earned | Invoice | Status |
|-------------|------------|-----------------|-----|-------------|-----------|---------|--------|
| INV-001 | 01-07-26 | Product A | 2 | ₹1000 | 360 CC | ⬇️ | Completed |

### Features
- Invoice download button (PDF)
- Status badges (Completed, Pending, Cancelled)
- Search by invoice number or date range

### Files to Create
| File | Purpose |
|------|---------|
| `resources/views/user/orders/purchase-history.blade.php` | Purchase history view |
| `app/Http/Controllers/User/OrderController.php` | User order controller |

---

## 5.2 Invoice Generation

**New File:** `app/Services/InvoiceService.php`

```php
class InvoiceService
{
    public function generate(Order $order): string
    {
        // Generate unique invoice number
        $invoiceNo = 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT);
        $order->update(['invoice_number' => $invoiceNo]);

        // Generate PDF
        $pdf = Pdf::loadView('invoices.order', ['order' => $order]);
        $path = "invoices/{$invoiceNo}.pdf";
        Storage::put($path, $pdf->output());

        return $path;
    }

    public function emailInvoice(Order $order): void
    {
        $path = $this->generate($order);
        Mail::to($order->user->email)->send(new OrderInvoiceMail($order, $path));
    }
}
```

### Files to Create
| File | Purpose |
|------|---------|
| `app/Services/InvoiceService.php` | Invoice generation logic |
| `resources/views/invoices/order.blade.php` | Invoice PDF template |
| `app/Mail/OrderInvoiceMail.php` | Invoice email |
| `resources/views/emails/order-invoice.blade.php` | Invoice email template |

### Integration Points
- Call `InvoiceService@emailInvoice` in `PurchaseService@purchase` after order creation
- Download route: `GET /user/invoice/{orderId}/download`

---

## 5.3 Buy Product for Another User

**Feature already exists** in admin panel (`MLMOrderController@store` accepts `user_id`).

### Need to Add to User Panel
| File | Purpose |
|------|---------|
| `resources/views/user/orders/order-for-someone.blade.php` | "Order for Someone" form |
| `app/Http/Controllers/User/OrderForSomeoneController.php` | Controller |

### Flow
1. User enters recipient's User ID
2. System validates recipient exists
3. User selects products and quantity
4. User confirms order
5. Order created under recipient's account
6. Invoice generated and emailed to recipient
7. Recipient's binary/BV updated
8. Income credited to recipient's referral chain

### Route
```php
Route::get('/order-for-someone', [OrderForSomeoneController::class, 'create']);
Route::post('/order-for-someone', [OrderForSomeoneController::class, 'store']);
```

---

# Phase 6: Notification System (Week 8)

## 6.1 Database Notification System

**New Files:**
- `app/Notifications/UserRegistered.php`
- `app/Notifications/ProductPurchased.php`
- `app/Notifications/IncomeCredited.php`
- `app/Notifications/RankAchieved.php`
- `app/Notifications/RewardAchieved.php`
- `app/Notifications/WithdrawalApproved.php`
- `app/Notifications/TicketUpdate.php`
- `app/Notifications/AccountActivated.php`

### Notification Types
| Type | Trigger | Content |
|------|---------|---------|
| Registration Successful | After signup | "Welcome to the MLM system!" |
| Product Purchased | After order | "You purchased 2x Product A — 360 CC earned" |
| Income Credited | After income calc | "You earned 18 CC Direct Income from User XYZ" |
| Rank Achieved | RankService | "Congratulations! You are now a Bronze member!" |
| Reward Achieved | RankService | "Your Smartwatch reward has been issued!" |
| Withdrawal Approved | Admin action | "Your withdrawal of 500 CC has been approved" |
| Ticket Update | Grievance reply | "Your ticket #123 has been updated" |
| Account Activated | ActivationService | "Your account is now Active!" |

### In-App Notification Center

**New File:** `resources/views/user/notifications/index.blade.php`

### Features
- Notification bell icon in header with unread count badge
- Dropdown showing recent 5 notifications
- Full notification center page with pagination
- Mark as read / Mark all read
- Notification categories/filters

### Files to Create
| File | Purpose |
|------|---------|
| `app/Notifications/BaseNotification.php` | Base notification class |
| `app/Http/Controllers/User/NotificationController.php` | Notification controller |
| `resources/views/user/notifications/index.blade.php` | Notification center |
| `resources/views/user/partials/notification-bell.blade.php` | Bell icon partial |

---

## 5.2 Email Notification Enhancement

### New Mailable Classes
| Class | Trigger | Template |
|-------|---------|----------|
| `UserRegisteredMail` | After signup | `emails.user-registered` |
| `ReferralNotificationMail` | To sponsor | `emails.referral-notification` |
| `BinaryPositionMail` | After placement | `emails.binary-position` |
| `OrderInvoiceMail` | After purchase | `emails.order-invoice` |
| `AccountActivatedMail` | After activation | `emails.account-activated` |
| `RankAchievedMail` | Rank evaluation | `emails.rank-achieved` |
| `RewardAchievedMail` | Reward issued | `emails.reward-achieved` |

### Email Event List
| Event | Recipient | Timing |
|-------|-----------|--------|
| User Registration | New user | Immediate |
| Welcome Email | New user | After signup |
| Referral Notification | Sponsor | After referral signs up |
| Binary Position Assigned | New user | After tree placement |
| Product Purchase | Buyer | After order completion |
| Invoice | Buyer | After order completion |
| Account Activation | User | When 2nd product purchased |
| Reward Achievement | User | When rank reward issued |
| Rank Achievement | User | When new rank earned |

---

# Phase 7: Grievance & Support (Weeks 8-9)

## 7.1 WhatsApp Support

### Implementation
- WhatsApp Business API / wa.me link
- Pre-filled message with user info

### Files to Create
| File | Purpose |
|------|---------|
| `resources/views/user/support/whatsapp-button.blade.php` | WhatsApp button partial |
| `app/Services/WhatsAppService.php` | WhatsApp integration |

### Button Placement
- Dashboard sidebar
- Grievance cell page
- Header (quick support)

### WhatsApp Link Format
```
https://wa.me/91XXXXXXXXXX?text=Hi%2C%20I%20need%20help%20with%20(User%20ID%3A%20{username})
```

---

## 7.2 Callback Request Module

**New File:** `resources/views/user/support/callback-request.blade.php`

### Form Fields
- Preferred Date (date picker)
- Preferred Time (time picker)
- Issue Summary (textarea)
- Submit button

### Files to Create
| File | Purpose |
|------|---------|
| `app/Http/Controllers/User/CallbackController.php` | Callback controller |
| `resources/views/user/support/callback-request.blade.php` | Callback form |
| `app/Models/CallbackRequest.php` | CallbackRequest model |

### Admin Management
- View all callback requests
- Mark as called/resolved
- Add admin notes

---

## 7.3 Grievance Enhancements

### Statuses Update
| Current | New |
|---------|-----|
| open | ✓ Open |
| in_progress | ✓ In Progress |
| closed | ✓ Resolved |
| — | ✓ Closed |

### Files to Modify
| File | Change |
|------|--------|
| `app/Models/Grivance.php` | Add `resolved` and `closed` statuses |
| `app/Http/Controllers/Api/GrievanceController.php` | Add callback link |
| `resources/views/admin/pages/mlm/grievance-cell/index.blade.php` | Show callback requests |

---

# Phase 8: Reports & Admin (Weeks 9-10)

## 8.1 Withdrawal History Page

**New File:** `resources/views/user/withdrawals/history.blade.php`

### Table Columns
| Request Date | Withdrawal Amount | Charges | Payable Amount | Status | Transaction No. | Payment Date |
|-------------|-------------------|---------|----------------|--------|-----------------|--------------|
| 01-07-26 | 1000 CC | 10 CC | 990 CC | ✅ Paid | TXN-001 | 02-07-26 |

### Status Badges
- Pending (🟡)
- Approved (🟢)
- Rejected (🔴)
- Paid (✅)

### Files to Create
| File | Purpose |
|------|---------|
| `resources/views/user/withdrawals/history.blade.php` | Withdrawal history |
| `app/Http/Controllers/User/WithdrawalController.php` | User withdrawal controller |

---

## 8.2 Report Module

### New Controller: `app/Http/Controllers/Admin/ReportController.php`

### Report Types
| Report | Data Source | Filters |
|--------|-------------|---------|
| Purchase Report | `orders` + `order_items` | Date range, user, product |
| Income Report | `income_logs` | Date range, user, income type |
| Referral Income Report | `income_logs` (direct) | Date range, sponsor |
| Reward Achievement Report | `user_ranks` | Date range, rank |
| Rank Achievement Report | `user_ranks` | Date range, rank |
| Withdrawal Report | `fund_requests` | Date range, status |
| User Activity Report | `mlm_users` + `orders` + `income_logs` | Date range, user |

### Files to Create
| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/ReportController.php` | Report controller |
| `resources/views/admin/reports/index.blade.php` | Report dashboard |
| `resources/views/admin/reports/purchase.blade.php` | Purchase report |
| `resources/views/admin/reports/income.blade.php` | Income report |
| `resources/views/admin/reports/referral-income.blade.php` | Referral income report |
| `resources/views/admin/reports/reward.blade.php` | Reward report |
| `resources/views/admin/reports/rank.blade.php` | Rank report |
| `resources/views/admin/reports/withdrawal.blade.php` | Withdrawal report |
| `resources/views/admin/reports/user-activity.blade.php` | User activity |

### Export Features
- CSV export for all reports
- PDF export (using dompdf)

---

## 8.3 Admin Management Enhancements

### New Admin Pages
| Page | Purpose | Route |
|------|---------|-------|
| Rank Management | CRUD ranks, set thresholds | `/admin/ranks` |
| Reward Fulfillment | Mark rewards as delivered | `/admin/rewards` |
| Rank History | View all rank assignments | `/admin/user-ranks` |
| Callback Requests | Manage callbacks | `/admin/callbacks` |
| Notification Logs | View sent notifications | `/admin/notifications` |
| Invoice History | Browse all invoices | `/admin/invoices` |

### Files to Create
| File | Purpose |
|------|---------|
| `app/Http/Controllers/Admin/RankController.php` | Admin rank CRUD |
| `app/Http/Controllers/Admin/RewardController.php` | Reward management |
| `app/Http/Controllers/Admin/UserRankController.php` | User rank admin |
| `app/Http/Controllers/Admin/CallbackController.php` | Callback admin |
| `app/Http/Controllers/Admin/NotificationController.php` | Notification admin |
| `app/Http/Controllers/Admin/InvoiceController.php` | Invoice admin |

---

# Phase 9: Integration Testing (Weeks 10-11)

## Test Cases

### Registration
- [ ] User registers with valid referral ID
- [ ] User selects Left/Right binary position
- [ ] Privacy Policy checkbox validates
- [ ] Terms & Conditions checkbox validates
- [ ] Invalid referral ID shows error
- [ ] Welcome email sent
- [ ] Sponsor notified
- [ ] Binary position confirmation sent

### Activation
- [ ] New user is inactive
- [ ] Purchase 1 product → still inactive
- [ ] Purchase 2nd product → auto-activated
- [ ] Activation email sent
- [ ] Inactive user cannot earn income

### CC Calculation
- [ ] 1 product = 180 CC
- [ ] 2 products = 360 CC
- [ ] CC per_unit stored in order_item
- [ ] 1 CC = ₹1 conversion

### Rank & Reward
- [ ] Self Income CC tracks all income types
- [ ] 18,000 CC → 1 Star rank
- [ ] 45,000 CC → Bronze rank
- [ ] Reward progress resets after achievement
- [ ] Current rank displays on dashboard
- [ ] Rank badge shows on profile

### Income
- [ ] Direct income credited to sponsor
- [ ] Income log created with all fields
- [ ] Wallet balance updated
- [ ] Dashboard KPI reflects income

### Purchase for Another
- [ ] Can order for another user
- [ ] Invoice sent to ordered user
- [ ] Ordered user's purchase history updated
- [ ] Income credited to ordered user's chain

### Notifications
- [ ] In-app notification created
- [ ] Email sent for each event type
- [ ] Notification bell shows unread count
- [ ] Mark as read works

### Grievance & Support
- [ ] Ticket creation with attachment
- [ ] Callback request submitted
- [ ] WhatsApp button opens correct URL
- [ ] Admin can manage tickets

### Withdrawal
- [ ] Withdrawal request submitted
- [ ] Admin approves/rejects
- [ ] Withdrawal history shows correct data

---

# Phase 10: Deployment (Week 12)

## Pre-Deployment Checklist
- [ ] All migrations run successfully
- [ ] Seed data populated correctly
- [ ] Routes registered and protected
- [ ] Permissions assigned (Spatie roles)
- [ ] Email configuration tested
- [ ] PDF generation tested
- [ ] WhatsApp link configured
- [ ] Backup existing database
- [ ] Run migration on production
- [ ] Clear cache: `php artisan optimize`
- [ ] Test critical user flows

---

# Complete File Inventory

## New Files to Create (45 files)

### Models (4)
| # | File |
|---|------|
| 1 | `app/Models/Rank.php` |
| 2 | `app/Models/UserRank.php` |
| 3 | `app/Models/RewardProgress.php` |
| 4 | `app/Models/IncomeLog.php` |

### Services (6)
| # | File |
|---|------|
| 5 | `app/Services/ActivationService.php` |
| 6 | `app/Services/RankService.php` |
| 7 | `app/Services/IncomeService.php` |
| 8 | `app/Services/DashboardService.php` |
| 9 | `app/Services/InvoiceService.php` |
| 10 | `app/Services/WhatsAppService.php` |

### Controllers (14)
| # | File |
|---|------|
| 11 | `app/Http/Controllers/User/DashboardController.php` |
| 12 | `app/Http/Controllers/User/IncomeController.php` |
| 13 | `app/Http/Controllers/User/OrderController.php` |
| 14 | `app/Http/Controllers/User/OrderForSomeoneController.php` |
| 15 | `app/Http/Controllers/User/NotificationController.php` |
| 16 | `app/Http/Controllers/User/CallbackController.php` |
| 17 | `app/Http/Controllers/User/WithdrawalController.php` |
| 18 | `app/Http/Controllers/Api/IncomeController.php` |
| 19 | `app/Http/Controllers/Admin/RankController.php` |
| 20 | `app/Http/Controllers/Admin/RewardController.php` |
| 21 | `app/Http/Controllers/Admin/UserRankController.php` |
| 22 | `app/Http/Controllers/Admin/CallbackController.php` |
| 23 | `app/Http/Controllers/Admin/NotificationController.php` |
| 24 | `app/Http/Controllers/Admin/InvoiceController.php` |
| 25 | `app/Http/Controllers/Admin/ReportController.php` |

### Views (22)
| # | File |
|---|------|
| 26 | `resources/views/user/dashboard.blade.php` |
| 27 | `resources/views/user/layout/master.blade.php` |
| 28 | `resources/views/user/partials/rank-badge.blade.php` |
| 29 | `resources/views/user/partials/kpi-card.blade.php` |
| 30 | `resources/views/user/partials/rank-progress.blade.php` |
| 31 | `resources/views/user/partials/notification-bell.blade.php` |
| 32 | `resources/views/user/income/referral-income-log.blade.php` |
| 33 | `resources/views/user/orders/purchase-history.blade.php` |
| 34 | `resources/views/user/orders/order-for-someone.blade.php` |
| 35 | `resources/views/user/notifications/index.blade.php` |
| 36 | `resources/views/user/support/whatsapp-button.blade.php` |
| 37 | `resources/views/user/support/callback-request.blade.php` |
| 38 | `resources/views/user/withdrawals/history.blade.php` |
| 39 | `resources/views/invoices/order.blade.php` |
| 40 | `resources/views/admin/reports/index.blade.php` |
| 41 | `resources/views/admin/reports/purchase.blade.php` |
| 42 | `resources/views/admin/reports/income.blade.php` |
| 43 | `resources/views/admin/reports/referral-income.blade.php` |
| 44 | `resources/views/admin/reports/reward.blade.php` |
| 45 | `resources/views/admin/reports/rank.blade.php` |
| 46 | `resources/views/admin/reports/withdrawal.blade.php` |
| 47 | `resources/views/admin/reports/user-activity.blade.php` |

### Notifications (8)
| # | File |
|---|------|
| 48 | `app/Notifications/UserRegistered.php` |
| 49 | `app/Notifications/ProductPurchased.php` |
| 50 | `app/Notifications/IncomeCredited.php` |
| 51 | `app/Notifications/RankAchieved.php` |
| 52 | `app/Notifications/RewardAchieved.php` |
| 53 | `app/Notifications/WithdrawalApproved.php` |
| 54 | `app/Notifications/TicketUpdate.php` |
| 55 | `app/Notifications/AccountActivated.php` |

### Mail (7)
| # | File |
|---|------|
| 56 | `app/Mail/UserRegisteredMail.php` |
| 57 | `app/Mail/ReferralNotificationMail.php` |
| 58 | `app/Mail/BinaryPositionMail.php` |
| 59 | `app/Mail/OrderInvoiceMail.php` |
| 60 | `app/Mail/AccountActivatedMail.php` |
| 61 | `app/Mail/RankAchievedMail.php` |
| 62 | `app/Mail/RewardAchievedMail.php` |

### Migrations (8)
| # | File |
|---|------|
| 63 | `database/migrations/xxxx_create_ranks_table.php` |
| 64 | `database/migrations/xxxx_create_user_ranks_table.php` |
| 65 | `database/migrations/xxxx_create_callback_requests_table.php` |
| 66 | `database/migrations/xxxx_create_notifications_table.php` |
| 67 | `database/migrations/xxxx_create_reward_progress_table.php` |
| 68 | `database/migrations/xxxx_create_income_logs_table.php` |
| 69 | `database/migrations/xxxx_add_phase2_columns_to_mlm_users.php` |
| 70 | `database/migrations/xxxx_add_phase2_columns_to_orders.php` |

### Seeders (1)
| # | File |
|---|------|
| 71 | `database/seeders/RankSeeder.php` |

---

## Files to Modify (15 files)

| # | File | Changes |
|---|------|---------|
| 1 | `app/Models/MlmUser.php` | Add `currentRank()`, `activated_at` cast, `accepted_terms_at`, `accepted_privacy_at` |
| 2 | `app/Models/OrderItem.php` | Add `cc_per_unit`, `invoice_number` |
| 3 | `app/Models/Order.php` | Add `invoice_number`, `ordered_by_user_id`, `invoice_generated_at` |
| 4 | `app/Models/Grivance.php` | Add `resolved`/`closed` statuses, `resolved_at`, `callback_request_id` |
| 5 | `app/Models/CcPointSetting.php` | Update default rate to 1.00 |
| 6 | `app/Models/CCSetting.php` | Update default value to 1.00 |
| 7 | `app/Services/PurchaseService.php` | New CC calc, auto-activation, invoice, income logging, rank eval |
| 8 | `app/Services/PayoutService.php` | Use stored cc_points, log income, trigger notifications |
| 9 | `app/Http/Controllers/Api/UserRegisterController.php` | Add T&C, privacy, binary position selection |
| 10 | `app/Http/Controllers/MLM/RegistrationController.php` | Add T&C, privacy, binary position |
| 11 | `app/Http/Controllers/Api/GrievanceController.php` | Add callback link, WhatsApp support |
| 12 | `app/Http/Controllers/MLM/MLMUserController.php` | Update dashboard with rank KPI data |
| 13 | `resources/views/auth/register.blade.php` | Add T&C, privacy checkboxes, binary position |
| 14 | `resources/views/admin/pages/dashboard.blade.php` | Add rank distribution widget |
| 15 | `routes/web.php` | Add all new routes |

---

## Dependency Map

```
Registration Enhancements
  └── T&C / Privacy checkboxes
  └── Binary position selection
  └── Email notifications
        ├── Welcome email (Mailable)
        ├── Sponsor notification (Mailable)
        └── Binary position confirmation (Mailable)

Product Purchase (Core)
  ├── Minimum 2 products check
  ├── ActivationService.activate() ──────────────► AccountActivatedMail
  ├── InvoiceService.generate() ─────────────────► OrderInvoiceMail
  ├── IncomeService.creditIncome()
  │     ├── Wallet balance update
  │     ├── IncomeLog record
  │     ├── IncomeCredited notification
  │     └── DashboardService cache update
  ├── RankService.evaluate()
  │     ├── RankAchieved notification
  │     ├── RankAchievedMail
  │     └── Reward achievement check
  └── Notification: ProductPurchased

Dashboard
  └── DashboardService.getKpis()
        ├── income_logs (7 income types)
        └── RankService.getProgress()

Referral Income Log
  └── income_logs (filtered by user_id)

Purchase History
  └── orders + order_items

Grievance Cell
  ├── Ticket system (existing)
  ├── CallbackRequest module
  └── WhatsApp integration

Withdrawal History
  └── fund_requests (existing, new view)

Reports
  └── orders / income_logs / user_ranks / fund_requests
```

---

## Risk Assessment

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| CC value change (₹60→₹1) breaks existing payout expectations | High | High | Communicate change clearly; all CC values stay same, only ₹ equivalent changes |
| Existing users activated by email don't have 2 products | High | Medium | Backfill: count existing orders; if <2, set inactive and prompt purchase |
| Rank evaluation on every purchase impacts performance | Low | Medium | Use cached self_income_cc; recalculate only when income credited |
| Missing model imports (`AwardReward`, `CashBonusHistory`) cause 500 errors | High | High | Create stub models or remove dead imports in `WalletIncomeApiController` |
| Binary position selection during registration changes existing flow | Medium | Medium | Add as optional field; existing users stay in holding tank |
| Invoice PDF fails to generate | Low | Medium | Ensure dompdf is properly configured; add fallback HTML invoice |
| Email delivery failures | Low | Medium | Queue emails; log failures; add retry mechanism |
| WhatsApp number not configured | Low | Low | Make configurable via admin settings |

---

## Effort Summary

| Phase | Weeks | Estimated Hours |
|-------|-------|-----------------|
| Phase 1: Foundation & Database | 2 | 40h |
| Phase 2: Core Business Logic | 2 | 50h |
| Phase 3: User Dashboard & KPIs | 2 | 40h |
| Phase 4: Income & Wallet Systems | 1 | 30h |
| Phase 5: Purchase & Order Modules | 2 | 40h |
| Phase 6: Notification System | 1 | 30h |
| Phase 7: Grievance & Support | 1 | 30h |
| Phase 8: Reports & Admin | 2 | 40h |
| Phase 9: Integration Testing | 2 | 50h |
| Phase 10: Deployment | 1 | 20h |
| **Total** | **12-14 Weeks** | **~370 hours** |
