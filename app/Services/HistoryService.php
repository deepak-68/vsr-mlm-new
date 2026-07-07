<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderProcessingLog;

class HistoryService
{
    public function log(Order $order, string $step, string $status, array $details = []): OrderProcessingLog
    {
        return OrderProcessingLog::create([
            'order_id' => $order->id,
            'step'     => $step,
            'status'   => $status,
            'details'  => $details,
        ]);
    }

    public function logSuccess(Order $order, string $step, array $details = []): OrderProcessingLog
    {
        return $this->log($order, $step, 'success', $details);
    }

    public function logFailed(Order $order, string $step, string $error): OrderProcessingLog
    {
        return $this->log($order, $step, 'failed', ['error' => $error]);
    }

    public function logSkipped(Order $order, string $step, string $reason): OrderProcessingLog
    {
        return $this->log($order, $step, 'skipped', ['reason' => $reason]);
    }

    public function getProcessingLogs(int $orderId): array
    {
        return OrderProcessingLog::where('order_id', $orderId)
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function hasStep(Order $order, string $step): bool
    {
        return OrderProcessingLog::where('order_id', $order->id)
            ->where('step', $step)
            ->where('status', 'success')
            ->exists();
    }

    public function isAlreadyProcessed(Order $order): bool
    {
        return $this->hasStep($order, 'self_cc')
            || $this->hasStep($order, 'order_confirmed');
    }
}
