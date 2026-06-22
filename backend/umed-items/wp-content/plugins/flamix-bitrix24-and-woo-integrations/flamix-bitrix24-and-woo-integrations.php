<?php
/*
Plugin Name: Flamix: Bitrix24 and WooCommerce Orders integration
Plugin URI: https://flamix.solutions/bitrix24/integrations/site/woocommerce.php
Description: Automatic Lead or Deal creating in Bitrix24 from WooCommerce order
Author: Roman Shkabko (Flamix)
Version: 6.0.1
Author URI: https://flamix.info
License: GPLv2
*/

defined('ABSPATH') || exit;

use Flamix\Plugin\General\Checker;
use Flamix\Bitrix24\WooCommerce\Handlers;
use Flamix\Bitrix24\WooCommerce\Settings;
use FlamixLocal\WooOrders\Jobs\Order as OrderQueue;

if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    add_action('admin_notices', function () { echo '<div class="error notice"><p><b>Flamix: Bitrix24 and WooCommerce Orders integration</b>: Upgrade your PHP version. Minimum version - 7.4+. Your PHP version ' . PHP_VERSION . '! If you don\'t know how to upgrade PHP version, just ask in your hosting provider! If you can\'t upgrade - delete this plugin!</p></div>';});
    return false;
}

// TODO: Move to new SDK
define('FLAMIX_BITRIX24_WOOCOMMERCE_ORDER_DIR', __DIR__);
define('FLAMIX_BITRIX24_WOOCOMMERCE_ORDER_CODE', 'flamix-bitrix24-and-woo-integrations');

include_once __DIR__ . '/includes/vendor/autoload.php';
include_once __DIR__ . '/settings/Settings.php';
include_once __DIR__ . '/includes/Helpers.php';
include_once __DIR__ . '/includes/Handlers.php';

// Menu
if (is_admin()) {
    add_action('admin_menu', [Settings::class, 'add_menu']);
    add_filter('plugin_action_links_flamix-bitrix24-and-woo-integrations/flamix-bitrix24-and-woo-integrations.php', [Settings::class, 'add_link_to_plugin_widget']);
    add_action('wp_ajax_flamix_b24_woo_dispatch_order', [Handlers::class, 'ajax_dispatch_order']);
    add_action('wp_ajax_flamix_b24_woo_clear_queue', [Handlers::class, 'ajax_clear_queue']);
}

// Register handlers
if (Checker::isPluginActive('woocommerce')) {
    add_action('wp', [Handlers::class, 'trace']);
    add_action('woocommerce_new_order', [Handlers::class, 'new_order']);
    add_action('woocommerce_payment_complete', [Handlers::class, 'payment_complete']);
    add_action('woocommerce_order_status_changed', [Handlers::class, 'sendStatusToBitrix24']);
    add_action('woocommerce_thankyou', [Handlers::class, 'dispatchNotSentOrders']); // In old cart we use woocommerce_checkout_order_processed
}

// Create table for Queue
function flamix_bitrix24_and_woo_integrations_plugin_activate() {
    $queue = new OrderQueue();
    $queue->createQueueTableIfNotExist();
}
register_activation_hook(__FILE__, 'flamix_bitrix24_and_woo_integrations_plugin_activate');