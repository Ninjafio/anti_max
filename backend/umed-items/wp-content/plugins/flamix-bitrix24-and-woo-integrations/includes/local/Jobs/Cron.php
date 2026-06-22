<?php

namespace FlamixLocal\WooOrders\Jobs;

use FlamixLocal\WooOrders\Jobs\Order;

class Cron
{
    /**
     * Add new custom schedule interval.
     *
     * @param $schedules
     * @return mixed
     */
    public static function every_minute($schedules)
    {
        $schedules['flamix_woo_every_minute'] = [
            'interval' => 60, // 1 минута в секундах
            'display'  => esc_html__('Every Minute'),
        ];
        return $schedules;
    }

    /**
     * Silent dispatch.
     *
     * @return void
     */
    public static function dispatch()
    {
        // flamix_log("Cron is working!");
        try {
            $order = new Order();
            $order->dispatchAll();
        } catch (\Exception $e) {
            // flamix_log($e->getMessage());
        }
    }
}