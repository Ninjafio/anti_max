<?php

namespace FlamixLocal\WooOrders\Jobs;

use Flamix\Plugin\Queue\SQL;
use Flamix\Plugin\Queue\JobCommands;
use Flamix\Plugin\Queue\Interfaces\ShouldQueue;
use Flamix\Bitrix24\WooCommerce\Handlers;
use Flamix\Bitrix24\WooCommerce\Helpers;
use Exception;

class Order extends JobCommands implements ShouldQueue
{
    protected string $success = 'SENT';

    /**
     * Handler must be static.
     *
     * @param int $order_id
     * @return int
     * @throws Exception
     */
    public static function handler(int $order_id): int
    {
        $order = new self();
        return $order->dispatch($order_id, false);
    }

    /**
     * Return SQL bridge.
     * Raw SQL commands for work with JOBs.
     *
     * @return SQL
     */
    public function sqlClosure(): SQL
    {
        global $wpdb;
        return new SQL($wpdb->prefix . 'flamix_order_jobs', function ($query, ...$var) use ($wpdb) {
            return $wpdb->prepare($query, ...$var);
        });
    }

    /**
     * Make WP Query to DB and return result.
     *
     * @param string $query
     * @return array|object|\stdClass[]|null
     */
    public function query(string $query)
    {
        global $wpdb;
        return $wpdb->get_results($query);
    }

    /**
     * Send order to our server. This method DOES NOT check if order lock exist.
     *
     * @param int $order_id
     * @param bool $dispatch_all
     * @return int
     * @throws Exception
     */
    public function dispatch(int $order_id, bool $dispatch_all = true): int
    {
        // Add order_id to the Queue DB, or set status NEW if job already exist (when try to resend)
        $job_id = $this->createOrUpdate($order_id, ['order_job_status' => 'NEW', 'attempts' => 'increment', 'reserved_at' => 'now', 'updated_at' => 'now']);
        // Check if order exist! If not - delete from Queue
        $is_order_exist = wc_get_order($order_id) !== false;
        if (!$is_order_exist && $job_id) {
            $this->delete($job_id);
            return $job_id;
        }

        // Sending to our Server...
        try {
            if (defined('FLAMIX_BITRIX24_WOO_ORDERS_DELAY') && $dispatch_all) {
                $bitrix24 = [];
            } else {
                $bitrix24 = Handlers::order($order_id);
            }

            Helpers::log('[Info] Sent request to Flamix App Server', $bitrix24);
        } catch (Exception $exception) {
            Helpers::log("[Error] When sending order #{$order_id} has error: {$exception->getMessage()}");
        }

        // If our Server accept JOB - Marking in our DB as SENT
        if ($job_id > 0 && ($bitrix24['job'] ?? false) == true) {
            $this->update(['order_job_status' => $this->success], ['id' => $job_id]);
        }

        // Try to dispatch all if it's not sent by cron
        if ($dispatch_all) {
            $this->dispatchAll();
        }

        return $job_id;
    }

    /**
     * Get all not sent Orders and try to send again.
     *
     * This will use in cron.
     *
     * @return array
     * @throws Exception
     */
    public function dispatchAll(): array
    {
        $notSent = $this->query($this->sqlClosure()->notSending($this->success));
        if (empty($notSent)) return $notSent;

        // Lock ALL orders
        $orders_id = array_map(fn($item) => $item->order_id, $notSent);
        $this->lock($orders_id);

        foreach ($notSent as $order) {
            $this->dispatch($order->order_id, false);
        }

        return $notSent;
    }

    /**
     * Get all not sent Orders.
     *
     * @return array
     */
    public function getNotSentOrders(): array
    {
        return $this->query($this->sqlClosure()->notSending($this->success));
    }

    /**
     * Clear all Queue.
     *
     * @return void
     */
    public function clearQueue(): void
    {
        $this->query($this->sqlClosure()->clear());
    }

    /**
     * Delete JOB from Queue.
     *
     * @param  int  $job_id
     * @return void
     */
    public function delete(int $job_id): void
    {
        $this->query($this->sqlClosure()->clear($job_id));
    }
}