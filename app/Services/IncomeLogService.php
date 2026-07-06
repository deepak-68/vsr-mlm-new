<?php

namespace App\Services;

use App\Models\IncomeLog;
use App\Models\MlmUser;
use App\Models\Order;
use App\Models\PayoutBalance;

class IncomeLogService
{
    public function log(
        int $userId,
        string $incomeType,
        float $ccAmount,
        float $currencyAmount,
        ?int $fromUserId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $orderNumber = null,
        ?string $remarks = null
    ): IncomeLog {
        $balance = PayoutBalance::where('mlm_user_id', $userId)->first();
        $currentBalance = $balance?->total_earned ?? 0;

        return IncomeLog::create([
            'user_id' => $userId,
            'from_user_id' => $fromUserId,
            'income_type' => $incomeType,
            'cc_amount' => $ccAmount,
            'currency_amount' => $currencyAmount,
            'balance_after' => $currentBalance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'order_number' => $orderNumber,
            'remarks' => $remarks,
        ]);
    }

    public function logFromOrder(
        Order $order,
        int $earnerUserId,
        string $incomeType,
        float $ccAmount,
        float $currencyAmount,
        ?int $fromUserId = null,
        ?string $remarks = null
    ): IncomeLog {
        return $this->log(
            userId: $earnerUserId,
            incomeType: $incomeType,
            ccAmount: $ccAmount,
            currencyAmount: $currencyAmount,
            fromUserId: $fromUserId,
            referenceType: 'order',
            referenceId: $order->id,
            orderNumber: $order->invoice?->invoice_number,
            remarks: $remarks,
        );
    }
}
