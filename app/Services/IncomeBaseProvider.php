<?php

namespace App\Services;

use App\Models\Order;

class IncomeBaseProvider
{
    public function getBaseAmount(Order $order): float
    {
        return (float) ($order->total_amount ?? 0);
    }

    public function getLabel(): string
    {
        return 'Order Amount';
    }
}
