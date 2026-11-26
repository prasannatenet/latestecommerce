<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderRewardService
{
    public function creditForPaidOrder(Order $order): void
    {
        if ($order->payment_status !== 'paid' || ! $order->user_id) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->payment_status !== 'paid') {
                return;
            }

            $meta = $lockedOrder->payment_meta ?? [];
            $pricing = $meta['pricing'] ?? [];
            if (\array_key_exists('order_reward_coins', $pricing)) {
                return;
            }

            $rewardPercent = (float) (Setting::query()->value('gehna_coin_reward_percent') ?? 10);
            $rewardCoins = (int) floor(max(0, (float) $lockedOrder->total) * $rewardPercent / 100);

            if ($rewardCoins > 0) {
                User::whereKey($lockedOrder->user_id)->increment('gehna_coins', $rewardCoins);
            }

            $meta['pricing']['order_reward_coins'] = $rewardCoins;
            $meta['pricing']['order_reward_percent'] = $rewardPercent;
            $lockedOrder->update(['payment_meta' => $meta]);
        });
    }
}
