<?php

use FlamixLocal\WooOrders\Jobs\Order as OrderQueue;
use Flamix\Plugin\General\Checker;
use Flamix\Bitrix24\WooCommerce\Helpers;

$queue = new OrderQueue();
$queue_not_sent = $queue->getNotSentOrders();

?>
<h2><?php _e('Diagnostics', 'flamix-bitrix24-and-woo-integrations'); ?></h2>
<ul>
    <li><?php
        try {
            $status = \Flamix\Bitrix24\WooCommerce\Helpers::send(['status' => 'check'], 'check');
            echo Checker::params('Status', ($status['status'] ?? '') === 'success', [
                __('Working', 'flamix-bitrix24-and-woo-integrations'),
                __('Bad Domain or API Key', 'flamix-bitrix24-and-woo-integrations'),
            ]);
        } catch (\Exception $e) {
            echo Checker::params('Status', false, [
                __('Working', 'flamix-bitrix24-and-woo-integrations'),
                esc_html($e->getMessage()),
            ]);
        } ?></li>
    <li><?php echo Checker::params(__('WordPress WooCommerce activated', 'flamix-bitrix24-and-woo-integrations'), Checker::isPluginActive('woocommerce'), [
            __('Yes', 'flamix-bitrix24-and-woo-integrations'),
            __('No. You must install plugin!', 'flamix-bitrix24-and-woo-integrations'),
        ]); ?></li>
    <li><?php echo Checker::params('cURL', extension_loaded('curl')); ?></li>
    <li><?php echo Checker::params('SSL', is_ssl()); ?></li>
    <li><?php echo Checker::params('PHP version', version_compare(PHP_VERSION, '7.4.0') >= 0, [
            sprintf(__('Ok (%s)', 'flamix-bitrix24-and-woo-integrations'), PHP_VERSION),
            sprintf(__('Bad PHP version (%s)! Update PHP version on your hosting!', 'flamix-bitrix24-and-woo-integrations'), PHP_VERSION),
        ]); ?></li>
    <li><?php echo Checker::params(__('DB for Queue', 'flamix-bitrix24-and-woo-integrations'), $queue->createQueueTableIfNotExist(), [
            __('Yes', 'flamix-bitrix24-and-woo-integrations'),
            __('No. You must install plugin!', 'flamix-bitrix24-and-woo-integrations'),
        ]); ?></li>
    <li><?php echo Checker::params(__('Unsent Orders in Queue', 'flamix-bitrix24-and-woo-integrations'), count($queue_not_sent) <= 2, [
            __('No Orders in Queue', 'flamix-bitrix24-and-woo-integrations'),
            sprintf(__('There are %s orders in the queue for dispatch. Wait until they are sent (dispatch when order status changes)!', 'flamix-bitrix24-and-woo-integrations'), count($queue_not_sent)),
        ]); ?></li>
    <li><?php echo Checker::params('Backup email', !empty(Helpers::get_backup_email()), [
            sprintf(__('Valid (%s)', 'flamix-bitrix24-and-woo-integrations'), Helpers::get_backup_email()),
            __('Invalid', 'flamix-bitrix24-and-woo-integrations'),
        ]); ?></li>
</ul>

<h2>How its works</h2>
<iframe width="560" height="315" src="https://www.youtube.com/embed/mACtqEEAq-I" frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>