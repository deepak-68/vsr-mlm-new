<?php
namespace App\Services;

use App\Models\CCSetting;
use App\Models\MLMTree;
use App\Models\MlmUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WalletBalance;
use App\Models\WalletTransaction;
use App\Models\Invoice;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    private const MIN_PRODUCTS_FOR_ACTIVATION = 2;

    public function purchase(
        int $userId,
        int $productId,
        int $quantity,
        ?int $targetUserId = null,
        ?string $paymentMode = null,
        ?string $transactionNumber = null,
        ?string $paymentProofPath = null
    ): Order {
        $actualUserId = $targetUserId ?? $userId;

        $this->validateUserPosition($actualUserId);

        $product = Product::findOrFail($productId);

        $this->validateStock($product, $quantity);

        if ($targetUserId) {
            $this->validateUserExists($targetUserId);
        }

        return DB::transaction(function () use (
            $actualUserId,
            $product,
            $quantity,
            $userId,
            $targetUserId,
            $paymentMode,
            $transactionNumber,
            $paymentProofPath
        ) {
            $unitPrice = $product->discount_price ?? $product->price;
            $totalAmount = $unitPrice * $quantity;
            $ccPoints = ($product->cc_points ?? 0) * $quantity;

            $isManual = $paymentMode === Order::PAYMENT_MANUAL;

            $order = Order::create([
                'user_id' => $actualUserId,
                'purchased_for_user_id' => $userId,
                'package_id' => null,
                'order_date' => now(),
                'total_amount' => $totalAmount,
                'total_cc_points' => $ccPoints,
                'status' => $isManual ? Order::STATUS_PENDING : Order::STATUS_COMPLETED,
                'order_type' => $targetUserId ? Order::TYPE_ADMIN : Order::TYPE_SELF,
                'payment_mode' => $paymentMode ?? Order::PAYMENT_WALLET,
                'note' => "Purchased {$quantity}x {$product->name}",
                'transaction_number' => $transactionNumber,
                'payment_proof' => $paymentProofPath,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'cc_points' => $ccPoints,
                'status' => $isManual ? 'PENDING' : 'COMPLETED',
            ]);

            $product->decrement('stock', $quantity);

            MLMTree::where('mlm_user_id', $actualUserId)
                ->update([
                    'business_volume' => DB::raw("business_volume + {$ccPoints}"),
                    'earned_amount' => DB::raw("earned_amount + {$totalAmount}")
                ]);

            if (!$isManual) {
                // Check activation (min 2 products purchased)
                $this->checkAndActivateUser($actualUserId);

                // Cumulative commission calculation
                $commission = $this->updateCommissionLevel($actualUserId);

                $this->generateDirectIncome(
                    $actualUserId,
                    $commission,
                    $order,
                    $quantity,
                );

                // Auto-generate invoice
                $this->generateInvoice($order, $userId, $ccPoints);

                // Send invoice email to the paying user
                try {
                    $invoice = Invoice::where('order_id', $order->id)->first();
                    $recipient = MlmUser::find($userId);
                    if ($invoice && $recipient) {
                        app(MailNotificationService::class)->sendInvoiceToUser($recipient, $invoice);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Invoice email failed: ' . $e->getMessage());
                }

                // Create in-app notification for both payer and recipient
                $this->createPurchaseNotification($actualUserId, $order, $ccPoints);
                if ($userId !== $actualUserId) {
                    $this->createPurchaseNotification($userId, $order, $ccPoints);
                }
            }

            return $order->load('items');
        });
    }

    public function checkAndActivateUser(int $userId): void
    {
        $user = MlmUser::find($userId);
        if (!$user || $user->is_active) return;

        $totalProducts = OrderItem::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 'COMPLETED');
        })->sum('quantity');

        if ($totalProducts >= self::MIN_PRODUCTS_FOR_ACTIVATION) {
            $user->update(['is_active' => true, 'is_verified' => true]);
        }
    }

    public function updateCommissionLevel(int $userId): int
    {
        $user = MlmUser::findOrFail($userId);

        // Calculate total lifetime quantity
        $totalQuantity = OrderItem::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 'COMPLETED');
        })->sum('quantity');

        // Commission tiers based on cumulative lifetime purchases
        $percentage = match (true) {
            $totalQuantity >= 40 => 20,
            $totalQuantity >= 20 => 18,
            $totalQuantity >= 12 => 16,
            $totalQuantity >= 6  => 14,
            $totalQuantity >= 2  => 12,
            default              => 10,
        };

        $user->update(['commission_percentage' => $percentage]);

        return $percentage;
    }

    public function generateDirectIncome(
        int $userId,
        int $commission,
        Order $order,
        int $quantity
    ): void {
        $sponsorId = MLMTree::where('mlm_user_id', $userId)->value('parent_id');

        if (!$sponsorId) {
            return;
        }

        // CC points from order × conversion rate (CC × ₹)
        $orderCC = (float) ($order->total_cc_points ?? 0);
        $ccRate = CCSetting::getActiveRate();
        $totalAmount = $orderCC * $ccRate;

        $this->creditWallet(
            userId: $sponsorId,
            amount: $totalAmount,
            type: 'credit',
            referenceId: $order->id,
            description: "Direct income from User {$userId}"
        );

        // Log to income_logs
        try {
            app(IncomeLogService::class)->logFromOrder(
                order: $order,
                earnerUserId: $sponsorId,
                incomeType: 'direct',
                ccAmount: $orderCC,
                currencyAmount: $totalAmount,
                fromUserId: $userId,
                remarks: "Direct income from User #{$userId} - {$quantity} product(s)"
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('IncomeLog failed: ' . $e->getMessage());
        }
    }

    private function validateStock(Product $product, int $quantity): void
    {
        if ($product->stock < $quantity) {
            throw new \DomainException('Product not available or insufficient stock');
        }
    }

    private function validateUserExists(int $userId): void
    {
        if (!MlmUser::where('id', $userId)->exists()) {
            throw new \DomainException('Target user not found.');
        }
    }

    private function validateUserPosition(int $userId): void
    {
        $parentId = MlmTree::where('mlm_user_id', $userId)->value('parent_id');

        if ($userId != 1 && !$parentId) {
            throw new \DomainException('You are not positioned under any sponsor.');
        }
    }

    public function generateInvoice(Order $order, int $userId, float $totalCc): void
    {
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'mlm_user_id' => $userId,
            'invoice_date' => now(),
            'total_amount' => $order->total_amount,
            'total_cc' => $totalCc,
            'status' => 'GENERATED',
        ]);

        // Generate PDF in background
        try {
            app(InvoiceService::class)->generate($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Invoice PDF generation failed: ' . $e->getMessage());
        }
    }

    public function createPurchaseNotification(int $userId, Order $order, float $ccPoints): void
    {
        Notification::create([
            'mlm_user_id' => $userId,
            'type' => 'purchase',
            'title' => 'Product Purchased',
            'message' => "You have successfully purchased products. Order #{$order->id} - {$ccPoints} CC earned.",
            'data' => ['order_id' => $order->id, 'cc_points' => $ccPoints],
        ]);
    }

    public function creditWallet(
        int $userId,
        float $amount,
        string $type,
        int $referenceId,
        string $description
    ): void {
        $wallet = WalletBalance::firstOrCreate(
            ['user_id' => $userId],
            [
                'wallet_id' => 1,
                'balance' => 0,
                'total_earned' => 0
            ]
        );

        $wallet->increment('balance', $amount);
        $wallet->increment('total_earned', $amount);

        $wallet->refresh();

        WalletTransaction::create([
            'wallet_id' => $wallet->wallet_id,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'reference_type' => Order::class,
            'reference_id' => $referenceId,
            'status' => 'completed',
            'description' => $description
        ]);
    }
}
