<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MLMTree;
use App\Models\MlmUser;
use Illuminate\Support\Facades\Log;

class SelfCCService
{
    /**
     * Get the user who should receive CC credit for an order.
     * CC goes to the sponsor of the purchased-for user (recipient).
     * If the recipient has no sponsor, CC stays with the recipient.
     */
    public function getCcRecipient(Order $order): ?MlmUser
    {
        $recipientId = $order->purchased_for_user_id ?? $order->user_id;
        $recipient = MlmUser::find($recipientId);

        if (!$recipient) {
            return null;
        }

        // CC goes to the sponsor of the recipient
        if ($recipient->sponsor_id) {
            return $recipient->sponsor;
        }

        return $recipient;
    }

    public function getTotalSelfCC(int $userId): float
    {
        return (float) OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)->where('status', Order::STATUS_COMPLETED);
        })->sum('cc_points');
    }

    /**
     * Get total CC attributed to a user as a sponsor
     * (CC from orders where this user is the sponsor of the purchased_for_user).
     */
    public function getTotalCcAsSponsor(int $sponsorId): float
    {
        return (float) OrderItem::whereHas('order', function ($q) use ($sponsorId) {
            $q->whereIn('purchased_for_user_id', function ($sub) use ($sponsorId) {
                $sub->select('id')
                    ->from('mlm_users')
                    ->where('sponsor_id', $sponsorId);
            })->where('status', Order::STATUS_COMPLETED);
        })->sum('cc_points');
    }

    public function getOrderCC(Order $order): float
    {
        return (float) $order->total_cc_points;
    }

    public function syncBusinessVolume(int $userId): void
    {
        $total = $this->getTotalSelfCC($userId);
        MLMTree::where('mlm_user_id', $userId)->update(['business_volume' => $total]);
    }

    public function logAccumulation(Order $order): array
    {
        $cc = $this->getOrderCC($order);
        $ccRecipient = $this->getCcRecipient($order);
        $recipientId = $ccRecipient ? $ccRecipient->id : $order->user_id;
        $totalAfter = $this->getTotalSelfCC($recipientId);

        Log::info('SelfCC accumulated', [
            'order_id' => $order->id,
            'credited_to_user_id'  => $recipientId,
            'added_cc' => $cc,
            'total_cc' => $totalAfter,
        ]);

        return [
            'credited_user_id' => $recipientId,
            'added_cc'   => $cc,
            'total_after' => $totalAfter,
        ];
    }
}
