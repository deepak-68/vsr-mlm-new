<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\MlmUser;
use App\Services\PurchaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderConfirmationService
{
    private array $pipeline = [];

    public function __construct(
        private readonly SelfCCService $selfCCService,
        private readonly IncomeService $incomeService,
        private readonly NotificationService $notificationService,
        private readonly HistoryService $historyService,
        private readonly PurchaseService $purchaseService,
        private readonly MailNotificationService $mailService,
        private readonly PayoutService $payoutService,
        private readonly BinaryMatchingService $binaryMatchingService,
        private readonly LevelIncomeService $levelIncomeService,
        private readonly RepurchaseIncomeService $repurchaseIncomeService,
        private readonly RankService $rankService,
        private readonly RewardTourService $rewardTourService,
    ) {
        $this->pipeline = [
            'order_confirmed'    => fn(Order $o) => $this->stepConfirmOrder($o),
            'self_cc'            => fn(Order $o) => $this->stepAccumulateSelfCC($o),
            'direct_income'      => fn(Order $o) => $this->stepDirectIncome($o),
            'matching_income'    => fn(Order $o) => $this->stepMatchingIncome($o),
            'level_income'       => fn(Order $o) => $this->stepLevelIncome($o),
            'repurchase_income'  => fn(Order $o) => $this->stepRepurchaseIncome($o),
            'rank_check'         => fn(Order $o) => $this->stepRankCheck($o),
            'invoice'            => fn(Order $o) => $this->stepInvoice($o),
            'notification'       => fn(Order $o) => $this->stepNotification($o),
            'email_invoice'      => fn(Order $o) => $this->stepEmailInvoice($o),
            'email_sponsor_cc'   => fn(Order $o) => $this->stepEmailSponsorCc($o),
        ];
    }

    public function confirm(int $orderId): array
    {
        $order = Order::with(['items', 'user', 'purchasedForUser'])->findOrFail($orderId);

        if (!$this->canBeConfirmed($order)) {
            throw new \DomainException('Order cannot be confirmed. Current status: ' . $order->status);
        }

        if ($this->historyService->isAlreadyProcessed($order)) {
            throw new \DomainException('Order has already been processed.');
        }

        $results = [];

        DB::transaction(function () use ($order, &$results) {
            $order->lockForUpdate();

            foreach ($this->pipeline as $step => $callback) {
                if ($this->historyService->hasStep($order, $step)) {
                    Log::info("Skipping already-processed step '{$step}'", ['order_id' => $order->id]);
                    $results[$step] = ['skipped' => true, 'reason' => 'Already processed'];
                    continue;
                }

                try {
                    $result = $callback($order);
                    $this->historyService->logSuccess($order, $step, $result ?? []);
                    $results[$step] = $result;
                } catch (\Throwable $e) {
                    Log::error("OrderConfirmation pipeline failed at step '{$step}'", [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                    $this->historyService->logFailed($order, $step, $e->getMessage());
                    throw $e;
                }
            }
        });

        Log::info('Order confirmed successfully', [
            'order_id' => $order->id,
            'results'  => $results,
        ]);

        return [
            'success' => true,
            'message' => 'Order confirmed and all processing completed successfully.',
            'results' => $results,
        ];
    }

    public function canBeConfirmed(Order $order): bool
    {
        return $order->status === Order::STATUS_PENDING
            && $order->payment_mode === Order::PAYMENT_MANUAL;
    }

    public function getPipelineSteps(): array
    {
        return array_keys($this->pipeline);
    }

    private function getRecipientUser(Order $order): ?MlmUser
    {
        $recipientId = $order->purchased_for_user_id ?? $order->user_id;
        return MlmUser::find($recipientId);
    }

    private function stepConfirmOrder(Order $order): array
    {
        $order->update(['status' => Order::STATUS_COMPLETED]);
        OrderItem::where('order_id', $order->id)->update(['status' => 'COMPLETED']);

        return [
            'order_id'     => $order->id,
            'new_status'   => Order::STATUS_COMPLETED,
        ];
    }

    private function stepAccumulateSelfCC(Order $order): array
    {
        return $this->selfCCService->logAccumulation($order);
    }

    private function stepDirectIncome(Order $order): array
    {
        $user = $order->user;
        $commission = $user?->commission_percentage ?? 10;
        $quantity = (int) $order->items->sum('quantity');

        return $this->incomeService->processDirectIncome($order, $quantity, $commission);
    }

    private function stepMatchingIncome(Order $order): array
    {
        $ccPoints = (float) ($order->total_cc_points ?? 0);

        if ($ccPoints <= 0) {
            return ['matched' => false, 'reason' => 'No CC points'];
        }

        $results = $this->binaryMatchingService->processOrderMatching($order, $ccPoints);

        $totalPairs = collect($results)->sum('pairs');
        $totalIncome = collect($results)->sum('income');

        return [
            'matched' => !empty($results),
            'ancestors_matched' => count($results),
            'total_pairs' => $totalPairs,
            'total_income' => $totalIncome,
            'details' => $results,
        ];
    }

    private function stepLevelIncome(Order $order): array
    {
        $ccPoints = (float) ($order->total_cc_points ?? 0);

        if ($ccPoints <= 0) {
            return ['levels_distributed' => 0];
        }

        $results = $this->levelIncomeService->processLevelIncome($order, $ccPoints);

        return [
            'levels_distributed' => count($results),
            'details' => $results,
        ];
    }

    private function stepRepurchaseIncome(Order $order): array
    {
        $userId = $order->user_id;

        if (!$this->repurchaseIncomeService->isRepurchase($userId)) {
            return ['is_repurchase' => false, 'reason' => 'First purchase'];
        }

        $ccPoints = (float) ($order->total_cc_points ?? 0);
        $results = $this->repurchaseIncomeService->processRepurchaseIncome($order, $ccPoints);

        return [
            'is_repurchase' => true,
            'commission_generated' => !empty($results),
            'details' => $results,
        ];
    }

    private function stepRankCheck(Order $order): array
    {
        $userId = $order->user_id;

        $userRank = $this->rankService->checkAndUpgradeRank($userId);

        if ($userRank) {
            $this->notificationService->createRankNotification(
                $userId,
                $userRank->rank->name ?? 'New Rank'
            );

            $this->rewardTourService->checkAndGenerateRewardIncome($userId, $userRank->rank_id);

            $rankIncomeAmount = (float) ($userRank->rank?->reward?->value_cc ?? 0);

            return [
                'rank_upgraded' => true,
                'rank_name' => $userRank->rank->name ?? 'Unknown',
                'rank_income_cc' => $rankIncomeAmount,
            ];
        }

        return [
            'rank_upgraded' => false,
        ];
    }

    private function stepInvoice(Order $order): array
    {
        $cc = $this->selfCCService->getOrderCC($order);
        $recipient = $this->getRecipientUser($order);
        $invoiceUserId = $recipient ? $recipient->id : $order->user_id;

        $this->purchaseService->generateInvoice($order, $invoiceUserId, $cc);

        return [
            'invoice_generated' => true,
            'user_id'           => $invoiceUserId,
        ];
    }

    private function stepNotification(Order $order): array
    {
        $count = 0;

        $recipient = $this->getRecipientUser($order);
        $recipientId = $recipient ? $recipient->id : $order->user_id;

        $this->notificationService->create(
            $recipientId,
            'purchase',
            'Order Confirmed',
            "Your order #{$order->id} has been confirmed. Thank you for your purchase."
        );
        $count++;

        if ($recipientId !== $order->user_id) {
            $payer = $order->user;
            if ($payer) {
                $this->notificationService->create(
                    $order->user_id,
                    'purchase',
                    'Order Confirmed for Someone',
                    "Your order #{$order->id} for {$recipient?->first_name} {$recipient?->last_name} has been confirmed."
                );
                $count++;
            }
        }

        return [
            'notifications_sent' => $count,
        ];
    }

    private function stepEmailInvoice(Order $order): array
    {
        $invoice = Invoice::where('order_id', $order->id)->first();
        if (!$invoice) {
            return ['email_sent' => false, 'reason' => 'No invoice found'];
        }

        $recipient = $this->getRecipientUser($order);
        if (!$recipient || !$recipient->email) {
            return ['email_sent' => false, 'reason' => 'Recipient has no email'];
        }

        try {
            $this->mailService->sendInvoiceToUser($recipient, $invoice);
            return ['email_sent' => true, 'to' => $recipient->email, 'type' => 'invoice'];
        } catch (\Throwable $e) {
            Log::warning('Failed to send invoice email: ' . $e->getMessage());
            return ['email_sent' => false, 'error' => $e->getMessage()];
        }
    }

    private function stepEmailSponsorCc(Order $order): array
    {
        $ccRecipient = $this->selfCCService->getCcRecipient($order);
        if (!$ccRecipient || !$ccRecipient->email) {
            return ['email_sent' => false, 'reason' => 'No sponsor or no email'];
        }

        $recipient = $this->getRecipientUser($order);
        if (!$recipient) {
            return ['email_sent' => false, 'reason' => 'No recipient found'];
        }

        $ccPoints = $this->selfCCService->getOrderCC($order);

        try {
            $this->mailService->sendSponsorCc($ccRecipient, $recipient, $order, $ccPoints);
            return [
                'email_sent' => true,
                'to'         => $ccRecipient->email,
                'type'       => 'sponsor_cc',
                'cc_points'  => $ccPoints,
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to send sponsor CC email: ' . $e->getMessage());
            return ['email_sent' => false, 'error' => $e->getMessage()];
        }
    }
}
