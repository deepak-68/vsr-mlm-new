# Change Request Implementation Plan

> Based on: MLM Software Change Request Document (July 1, 2026)
> Projects: Admin Panel + User Panel

---

## Architecture Overview

```
[User Panel (Laravel 12)]  ←──HTTP/JSON (100% API)──→  [Admin Panel (Laravel 12)]
   - Pure thin UI client                                 - All business logic & DB
   - Zero direct database access                         - Single source of truth
   - All data via Admin Panel API                        - Sanctum API auth
   - Sessions via API tokens                             - All migrations here
```

**Key Change:** User panel no longer shares the MySQL database. Every operation — including product purchase — goes through the Admin Panel API. The user panel has its own SQLite or retains only the `sessions` table for auth.

## Public Identifier System (UUID / Reference Codes)

**✅ DECIDED: Removed — now using internal `id` directly instead of `public_id`.**

- No UUID/public_id columns on any table
- API endpoints and responses use internal `id` (auto-increment) directly
- `track_id` and `user_name` are still used for referrals where applicable
- The `public_identifiers` table and all `public_id` columns have been removed via migration `2026_07_06_000011_drop_public_id_columns`

---

## Key Discovery: CC Value Change

Phase 1 uses **1 CC = ₹60** (`PayoutConfig.cc_to_currency_rate = 60`, `CCSetting.value = 60`).
Phase 2 changes to **1 CC = ₹1**. This ripples through every calculation.

---

## Implementation Modules

### Module 1: CC Value Reconfiguration

**Affects:** Admin Panel

- Change `CCSetting.value` default from 60 → 1
- Update `PayoutConfig.cc_to_currency_rate` from 60 → 1
- Recalculate all existing balances/transactions (data migration)
- Update `PayoutService.php` to use new rate

### Module 2: Product Activation Rule (Min 2 Products, No Max Limit)

**Affects:** Admin Panel + User Panel

- Add `min_products_for_activation` to `PayoutConfig` or new `BusinessConfig` model
- Update `PurchaseService.php` to activate user when total products >= 2
- **Remove all max purchase limits** — no 40-product cap, users can buy unlimited quantities
- Update `PurchaseService::updateCommissionLevel()` — commission tiers should be based on total lifetime purchases (cumulative), not per-transaction quantity cap
- User panel `ProductController@purchase()` — refactored to call Admin API instead of direct DB writes
- Inactive users restricted to Buy Now page only
- Add "Activation Status" column to admin user management

### Module 3: Enhanced Dashboard KPIs

**Affects:** Admin Panel + User Panel

7 KPI cards replacing existing layout:

| KPI | Source |
|-----|--------|
| Retail Income | Direct sales commission |
| Direct Income | Sponsor commission |
| Matching Income | Binary pair commission |
| Level Income | Generation-level commission |
| Reward & Tour Income | Reward achievements |
| Repurchase Income | Repeat purchase commission |
| Rank Income | Rank-based bonus |

Each card shows: Current Income (CC), Equivalent Amount (₹), Lifetime Total.

### Module 4: Rank/Badge System

**Affects:** Admin Panel + User Panel — Full stack new feature

**Database:**

- `ranks` — id, name, slug, required_self_cc, sort_order, reward_description, is_active
- `user_ranks` — id, mlm_user_id, rank_id, achieved_at, current_cc_at_time, is_current

| Rank | Required Self CC |
|------|-----------------|
| 1 Star | 18,000 CC |
| Bronze | 45,000 CC |
| Silver | 90,000 CC |
| Gold | 3,00,000 CC |
| Ruby | 7,20,000 CC |
| Crown | 81,00,000 CC |

**Admin:** `RankController` (CRUD), `UserRank` management, rank badge on user profiles.

**User:** Auto rank-upgrade service, rank badge on Dashboard / Profile / Team View.

**Logic (`RankService`):** Check after every qualifying purchase. If self CC meets threshold, create `UserRank`, send email, trigger reward.

### Module 5: Reward System

**Affects:** Admin Panel + User Panel — Full stack new feature

**Database:**

- `rewards` — id, rank_id, reward_name, reward_value_cc, reward_type
- `user_rewards` — id, mlm_user_id, reward_id, rank_id, achieved_at, claimed_at, status

| Rank | Reward |
|------|--------|
| 1 Star | Company Kit + Catalogue + Uniform |
| Bronze | Smartwatch |
| Silver | LED TV + Shimla Tour |
| Gold | Bike |
| Ruby | Car or Car Fund (₹7,00,000) |
| Crown | House (₹25,00,000) |

**Rule:** Once reward achieved, qualification progress resets to zero for next level.

### Module 6: Registration Enhancements

**Affects:** User Panel + Admin Panel

- Privacy Policy checkbox (mandatory)
- Terms & Conditions checkbox (mandatory)
- Binary Position Selection (Left / Right)
- Sponsor verification
- New columns: `privacy_policy_accepted`, `terms_accepted` on `mlm_users`

### Module 7: Invoice Generation

**Affects:** Admin Panel — New feature

- `invoices` table — id, order_id, invoice_number, mlm_user_id, invoice_date, total_amount, total_cc, pdf_path, status
- `InvoiceService` — PDF via `barryvdh/laravel-dompdf`
- Format: `INV-{YYYYMMDD}-{XXXX}`
- Auto-generate on order complete

### Module 8: Purchase History Page

**Affects:** User Panel (enhancement)

- Invoice Number, Order Date, Product Details, Quantity, Total Amount, CC Earned, Invoice Download, Order Status
- Date range filter, Export to CSV
- Admin global purchase history view
- **User panel fetches all data via API** — no direct DB queries for order history

### Module 9: Referral Income Log

**Affects:** User Panel + Admin Panel — New feature

- Date, Income Type, From User ID, From User Name, Order Number, Purchase CC, Income Credited, Current Balance, Remarks
- Search/filter by date, income type

### Module 10: Order Product for Someone Else

**Affects:** User Panel + Admin Panel — New feature

- Enter User ID / Track ID → Search → Select Products → Place Order
- Invoice for target user, binary/BV updated for target user's chain, income credited to target user's referral chain
- `POST /order-for-someone` API endpoint (accepts `track_id` or user `id`)
- No direct DB access — user panel proxies entirely through API

### Module 11: Email Notification System

**Affects:** Admin Panel — Enhancement

New mail classes:
- WelcomeEmail, SponsorNotificationEmail, BinaryPositionEmail
- InvoiceEmail, AccountActivationEmail
- RewardAchievedEmail, RankAchievedEmail
- WithdrawalApprovedEmail, TicketUpdateEmail

`MailNotificationService` manages all sending via queued jobs.

### Module 12: In-App Notification System

**Affects:** Admin Panel + User Panel — Full stack new feature

- `notifications` table — id, mlm_user_id, type, title, message, data_json, is_read, created_at
- Notification bell in user header with unread badge
- Notification list page
- Types: Registration, Purchase, Income, Reward, Rank, Withdrawal, Ticket

### Module 13: Grievance Cell Enhancement

**Affects:** User Panel + Admin Panel

**Callback Scheduling:**
- `callback_requests` table — preferred_date, preferred_time, issue_summary, status
- User "Schedule Callback" button
- Admin management view

**WhatsApp Support:**
- `wa.me` link with pre-filled message
- Configurable WhatsApp number in settings

**Ticket Enhancement:**
- Status flow: Open → In Progress → Resolved → Closed
- Priority: Low, Medium, High

### Module 14: Withdrawal Module Enhancement

**Affects:** User Panel

- Redesign `withdrawal-history.blade.php` with full DataTable: Request Date, Amount, Charges, Payable, Status, Transaction Number, Payment Date
- Statuses: Pending, Approved, Rejected, Paid
- Fix dead code in `FundHistoryController::withdrawalHistory()`

### Module 15: Income Automation

**Affects:** Admin Panel — Backend logic

Enhanced `PurchaseService` after every qualifying purchase:
1. Calculate business CC (exists — remove cap, cumulative lifetime)
2. Credit sponsor income (exists)
3. Update binary business (exists — track cumulative BV, no reset)
4. Update rank qualification (new — call `RankService`)
5. Update reward qualification (new — call `RewardService`)
6. Generate wallet entries (exists)
7. Record income logs (new)
8. Create in-app notification (new — call `NotificationService`)
9. Send email notifications (new — call `MailNotificationService`)

**Product purchase API endpoint** (`POST /purchase`) — created in Admin Panel so user panel calls API instead of writing directly to DB.

### Module 16: Admin Management for New Modules

**Affects:** Admin Panel

| Page | Purpose |
|------|---------|
| `/admin/ranks` | Manage rank definitions |
| `/admin/user-ranks` | View user rank achievements |
| `/admin/rewards` | Configure rewards per rank |
| `/admin/user-rewards` | Manage reward claims |
| `/admin/purchase-history` | Global purchase history |
| `/admin/referral-income-logs` | User income logs |
| `/admin/notifications` | Notification logs |
| `/admin/callback-requests` | Manage callbacks |
| `/admin/reports/*` | Report generation |

### Module 17: Reports

**Affects:** Admin Panel — New feature

| Report | Source |
|--------|--------|
| Purchase Report | Orders + OrderItems |
| Income Report | WalletTransactions |
| Referral Income Report | ReferralIncomeLog |
| Reward Achievement Report | UserRewards |
| Rank Achievement Report | UserRanks |
| Withdrawal Report | FundRequests |
| User Activity Report | Notifications + Orders |

Export: CSV (DataTables), PDF (domPDF). Date range filters + summary stats.

---

## Database Migration Summary (Admin Panel)

### New Tables

1. `ranks`
2. `user_ranks`
3. `rewards`
4. `user_rewards`
5. `invoices`
6. `notifications`
7. `callback_requests`
8. `referral_income_logs` (optional — may reuse `payout_transactions`)
9. ~~`public_identifiers` — id, model_type, model_id, public_id (UUID unique), created_at~~ ❌ **Removed**

### New / Modified Columns

- `mlm_users`:
  - `privacy_policy_accepted` (bool)
  - `terms_accepted` (bool)
- ~~`public_id` (UUID, unique, indexed) — public-facing identifier~~ ❌ **Removed**
  - Remove or hide auto-increment `id` from all API responses
- ~~`orders`: Add `public_id` (UUID) for order reference in URLs and invoices~~ ❌ **Removed**
- ~~`invoices`: Add `public_id` (UUID)~~ ❌ **Removed**
- ~~`grivances` (tickets): Add `public_id` (UUID)~~ ❌ **Removed**
- ~~`fund_requests`: Add `public_id` (UUID)~~ ❌ **Removed**

### Remove / Refactor

- Remove `products_for_payout` hard cap of 40 from `PayoutConfig` — allow unlimited purchases
- Update `PurchaseService` — remove `max 40` lifetime check in `purchase()` method
- Update commission level logic to be cumulative lifetime-based, not per-transaction capped

---

## User Panel Changes Summary

**Cross-cutting: All direct DB access removed. Every operation uses Admin Panel API only.**

| View | Action |
|------|--------|
| `auth/register.blade.php` | Add privacy/terms, binary position, sponsor validation |
| `user/registration.blade.php` | Same additions |
| `dashboard.blade.php` | Redesign KPIs to 7 income types |
| `buy-now.blade.php` | Min 2 product enforcement, remove max limit |
| `order-history.blade.php` | Enhanced with invoice download, CC earned |
| New: `referral-income-log.blade.php` | Referral income log |
| New: `order-for-someone.blade.php` | Order for another user |
| `withdrawal-history.blade.php` | Redesigned with full DataTable |
| `grievance/*.blade.php` | Add callback, WhatsApp |
| `sidebar.blade.php` | Unhide wallet items, add new links |
| New: `notifications.blade.php` | Notification list |
| `components/top-header.blade.php` | Notification bell |
| `genealogy.blade.php` | Add rank badge |
| `edit-my-profile.blade.php` | Add rank badge |
| `ProductController.php` | **Refactor entirely** — remove all direct DB writes, call `POST /api/purchase` instead |
| `config/database.php` | Remove MySQL config for user panel (use SQLite or keep only sessions) |
| `.env` | Remove `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — user panel no longer connects to shared DB |

### User Panel Database Cleanup

After migrating to pure API:
- User panel can drop tables: `orders`, `order_items`, `products`, `wallet_balances`, `mlm_trees`, `mlm_users` (mirror tables)
- Keep only: `sessions`, `cache`, `jobs`, `failed_jobs` (framework tables)
- The `mlm_users` model in user panel becomes a local cache/session model only, not authoritative

---

## New / Modified API Endpoints (Admin Panel)

### Purchase API (replaces user panel direct DB writes)

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/api/purchase` | Process product purchase (no max limit). Accepts: `product_id`, `quantity`, `payment_mode`. Returns: order, invoice PDF link |
| `POST` | `/api/order-for-someone` | Order product for another user. Accepts: `target_track_id`, `product_id`, `quantity`, `payment_mode` |

### ~~UUID / Public ID Endpoints~~ ❌ **Removed**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET` | `/api/resolve-identifier` | Resolve `track_id` or user `id` (used by order-for-someone search) |
| All existing | All endpoints | Accept numeric `id` directly in URLs and params |

### ~~Modified API Responses~~ ❌ **Removed**

- ~~All JSON responses: replace `id` with `public_id` and `track_id` in user-facing data~~
- ~~Paginated responses: add `public_id` field to each record~~

---

## Admin Panel New Views Summary

| View | Purpose |
|------|---------|
| `ranks/index.blade.php` | List/manage rank definitions |
| `user-ranks/index.blade.php` | View user rank achievements |
| `rewards/index.blade.php` | Configure rewards per rank |
| `user-rewards/index.blade.php` | Manage reward claims |
| `purchase-history/index.blade.php` | Global purchase history |
| `referral-income-logs/index.blade.php` | User income logs |
| `notifications/index.blade.php` | Notification logs |
| `callback-requests/index.blade.php` | Manage callback scheduling |
| `reports/*.blade.php` | Report generation pages |
