<?php

namespace App\Services;

use App\Models\FundRequest;
use App\Models\IncomeLog;
use App\Models\Notification;
use App\Models\Order;
use App\Models\UserRank;
use App\Models\UserReward;

class ReportService
{
    public function getPurchaseReport($dateFrom, $dateTo)
    {
        return Order::with('user', 'items')
            ->select('orders.*')
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo . ' 23:59:59']);
    }

    public function getIncomeReport($dateFrom, $dateTo, $type = null)
    {
        $query = IncomeLog::with('user', 'fromUser')
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

        if ($type) {
            $query->where('income_type', $type);
        }

        return $query;
    }

    public function getReferralIncomeReport($dateFrom, $dateTo)
    {
        return IncomeLog::with('user', 'fromUser')
            ->whereIn('income_type', ['referral_commission', 'referral_bonus', 'referral_income'])
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
    }

    public function getRewardAchievementReport($dateFrom, $dateTo)
    {
        return UserReward::with('user', 'reward', 'rank')
            ->whereBetween('achieved_at', [$dateFrom, $dateTo . ' 23:59:59']);
    }

    public function getRankAchievementReport($dateFrom, $dateTo)
    {
        return UserRank::with('user', 'rank')
            ->whereBetween('achieved_at', [$dateFrom, $dateTo . ' 23:59:59']);
    }

    public function getWithdrawalReport($dateFrom, $dateTo)
    {
        return FundRequest::with('user')
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
    }

    public function getUserActivityReport($dateFrom, $dateTo)
    {
        return Notification::with('user')
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);
    }
}
