<?php

namespace Flamix\Bitrix24\WooCommerce;

use Flamix\Bitrix24\Trace;
use Flamix\Bitrix24\SmartUTM;
use FlamixLocal\WooOrders\Jobs\Order as OrderQueue;
use FlamixLocal\WooOrders\Woo\Products;
use WC_Order;

class Handlers
{
    /**
     * For develop purpose.
     *
     * @return string
     */
    public static function getSubDomain(): string
    {
        return $_SERVER['SERVER_NAME'] === 'wp.test.chosten.com' ? 'devlead' : 'leadwoocommerce';
    }

    /**
     * Visited Page.
     */
    public static function trace()
    {
        $title = @wp_title('', false);
        $title = empty($title) ? false : $title;

        Trace::init($title);

        // Status changed receiver
        self::changeStatusOnSite();
    }

    /**
     * New order.
     * Main problem - this order don't have products.
     * We put this order to queue and send it later (first when status changed).
     *
     * @param int $order_id
     * @return void
     * @throws \Exception
     */
    public static function new_order(int $order_id): void
    {
        $order = new OrderQueue();
        $order->create($order_id);
    }

    /**
     * When we visit thank you page, we will send order to Bitrix24.
     * UTM will be added to order.
     *
     * @param int $order_id
     * @return void
     * @throws \Exception
     */
    public static function thankyou(int $order_id)
    {
        $order = new OrderQueue();
        $order->dispatch($order_id, true);
    }

    /**
     * Mark order as paid.
     *
     * @param  int  $order_id
     * @return void
     */
    public static function payment_complete(int $order_id): void
    {
        $order = wc_get_order($order_id);
        $price = $order->get_total();
        $currency = $order->get_currency();
        if ($price > 0 && $currency) {
            try {
                Helpers::send(['HOSTNAME' => SmartUTM::getMyHostname(), 'ORDER_ID' => $order_id, 'AMOUNT' => $price, 'CURRENCY' => $currency], 'status/pay');
            } catch (\Exception $e) {
                // dump("Payment error: {$e->getMessage()}");
                Helpers::sendError($e->getMessage());
            }
        }
    }

    /**
     * Try to send all not sent orders.
     *
     * @return void
     * @throws \Exception
     */
    public static function dispatchNotSentOrders()
    {
        $order = new OrderQueue();
        $order->dispatchAll();
    }

    /**
     * Add to queue by AJAX.
     *
     * @return void
     * @throws \Exception
     */
    public static function ajax_dispatch_order(): void
    {
        $order_id = intval($_POST['order_id'] ?? 0);
        if (!$order_id) {
            wp_die('Order ID is required');
        }

        // Dispatch order and send to Bitrix24
        $order = new OrderQueue();
        $order->dispatch($order_id, true);

        wp_die('Added to queue');
    }

    /**
     * Add to queue by AJAX.
     *
     * @return void
     * @throws \Exception
     */
    public static function ajax_clear_queue(): void
    {
        $order = new OrderQueue();
        $order->clearQueue();

        wp_die('Added to queue');
    }

    /**
     * New order.
     *
     * @param int $order_id
     * @return false|mixed|void
     */
    public static function order(int $order_id)
    {
        $order = new WC_Order($order_id);
        $data = $order->get_data();

        if (empty($data)) {
            return false;
        }

        $status = strtoupper(trim($order->get_status()));
        // Ignore drafts
        if ($status === 'CHECKOUT-DRAFT') {
            return false;
        }

        $fields = self::prepareFields($data);

        // PRODUCTS
        if (!$order->get_item_count()) {
            // Do not send order without products
            return ['status' => 'error', 'message' => 'No products'];
        }
        $products = self::prepareProducts($data['line_items']);

        // Currency
        if (!empty($fields['CURRENCY'])) {
            $currency_code = $fields['CURRENCY'];
            unset($fields['CURRENCY']);
        }

        // Shipping
        $fields['SHIPPING_METHOD'] = $order->get_shipping_method() ?? '';

        // Filter
        $fields = apply_filters('flamix_bitrix24_integrations_fields_filter', $fields);
        $products = apply_filters('flamix_bitrix24_integrations_product_filter', $products, $order_id);

        $additional_data = [
            'FIELDS' => array_merge($fields, ['TITLE' => "Site order #{$order_id}"]),
            'PRODUCTS' => $products,
        ];

        $additional_data['CURRENCY'] = !empty($currency_code) ? $currency_code : '';
        $additional_data['STATUS'] = $status;

        //Last filter
        $additional_data = apply_filters('flamix_bitrix24_integrations_filter', $additional_data);

        // dd($additional_data);
        try {
            return Helpers::send($additional_data);
        } catch (\Exception $e) {
            //echo 'Error: ',  $e->getMessage();
            Helpers::sendError($e->getMessage());
        }
    }

    /**
     * Prepare fields to send to Bitrix24.
     *
     * @param array $data
     * @return array
     */
    private static function prepareFields(array $data = []): array
    {
        // General
        $tmp['ORDER_ID'] = $data['id'] ?? null;
        $tmp['CURRENCY'] = $data['currency'] ?? null;
        $tmp['CUSTOMER_ID'] = $data['customer_id'] ?? null;
        $tmp['DELIVERY_PRICE'] = $data['shipping_total'] ?? null;
        $tmp['PAYMENT_METHOD'] = $data['payment_method_title'] ?? null;
        $tmp['TRANSACTIONS_ID'] = $data['transaction_id'] ?? null;
        $tmp['SHIPPING_PRICE'] = $data['shipping_total'] ?? null;
        $tmp['CUSTOMER_COMMENT'] = $data['customer_note'] ?? null;
        $tmp['DATE'] = date("Y-m-d H:i:s");

        // Billing
        if (!empty($data['billing']) && is_array($data['billing'])) {
            foreach ($data['billing'] as $billing_key => $billing_value) {
                if (!empty($billing_value)) {
                    $tmp[strtoupper("BILLING_{$billing_key}")] = $billing_value;
                }
            }
        }

        // Shipping
        if (!empty($data['shipping']) && is_array($data['shipping'])) {
            foreach ($data['shipping'] as $shipping_key => $shipping_value) {
                if (!empty($shipping_value)) {
                    $tmp[strtoupper("SHIPPING_{$shipping_key}")] = $shipping_value;
                }
            }
        }

        // TODO: In 7.4: array_filter($tmp ?? [], fn($value) => !is_null($value));
        return array_filter($tmp ?? [], function($value) {
            return !is_null($value);
        });
    }

    // TODO: Move to local products
    private static function prepareProducts(array $products = []): array
    {
        foreach ($products as $item_id => $item) {
            $product = $item->get_product();
            $quantity = floatval($item->get_quantity());
            $regular_price = round(floatval(!empty($product->get_regular_price()) ? $product->get_regular_price() : $product->get_price()), 2); // Can be empty and throw RuntimeException
            $price = round($item->get_total() / $quantity, 2);
            $sku = $product->get_sku();
            $product_id = $product->get_id();
            $tax = round($item->get_total_tax() / $quantity, 2);

            $tmp[$item_id]['ID'] = $product_id;
            $tmp[$item_id]['NAME'] = preg_replace('/\s?\(#\d+\)/', '', strip_tags($product->get_formatted_name())); // Correct work with variables
            $tmp[$item_id]['QUANTITY'] = $quantity;
            $tmp[$item_id]['PRICE'] = $price;

            if (!empty($sku)) {
                $tmp[$item_id]['XML_ID'] = $sku;
            }

            // TAX
            if ($tax) {
                $tmp[$item_id]['TAX_INCLUDED'] = 'Y'; // Tax always will be included
                $tmp[$item_id]['TAX_RATE'] = round(($tax / $price) * 100, 2);
                $price += $tax; // Price always without tax
                $tmp[$item_id]['PRICE'] = $price;
            }

            // If discounted
            if ($price != $regular_price) {
                $tmp[$item_id]['DISCOUNT_TYPE_ID'] = 1;
                $tmp[$item_id]['DISCOUNT_SUM'] = $regular_price - $price;
            }

            $find_by = self::getFindBy();
            if ($find_by === 'DISABLE') continue;

            if ($find_by === 'EXTERNAL_ID') {
                $tmp[$item_id]['FIND_BY'] = 'XML_ID';
                $tmp[$item_id]['XML_ID'] = $product->get_meta('EXTERNAL_ID');
            } else if ($find_by !== 'CUSTOM') {
                $tmp[$item_id]['FIND_BY'] = $find_by;
            } else {
                $product_find_by_woocommerce_attribute = Settings::getOption('product_find_by_woocommerce_attribute');
                $product_find_by_bitrix24_attribute = Settings::getOption('product_find_by_bitrix24_attribute');

                if (empty($product_find_by_bitrix24_attribute)) continue;

                switch ($product_find_by_woocommerce_attribute) {
                    case 'SKU':
                        $product_find_by_woocommerce_attribute_val = $sku;
                        break;
                    case 'NAME':
                        $product_find_by_woocommerce_attribute_val = $item->get_name();
                        break;
                    default:
                        $product_find_by_woocommerce_attribute_val = $product->get_attribute("pa_{$product_find_by_woocommerce_attribute}");
                        break;
                }

                if (empty($product_find_by_woocommerce_attribute_val)) continue;

                $tmp[$item_id]['FIND_BY'] = $product_find_by_bitrix24_attribute; // Dynamic FIND_BY
                $tmp[$item_id][$product_find_by_bitrix24_attribute] = $product_find_by_woocommerce_attribute_val; //Dynamic B24 Option
            }
        }

        // dd($tmp);
        return $tmp ?? [];
    }

    public static function getFindBy()
    {
        //TODO: Default
        if (!empty(Settings::getOption('product_find_by')))
            return Settings::getOption('product_find_by');

        return 'DISABLE';
    }

    /**
     * Site send to CRM needle Status
     *
     * @param int $order_id
     * @return bool
     * @throws \Exception
     */
    public static function sendStatusToBitrix24(int $order_id)
    {
        // Try to send not sent orders
        $queue = new OrderQueue();
        $queue->dispatchAll();
        // Check if order sent
        if (defined('FLAMIX_STATUS_CHANGED') || !$queue->isSentSuccessfully($order_id)) {
            return false;
        }

        $status = (new WC_Order($order_id))->get_status() ?? '';

        if (!empty($status)) {
            try {
                Helpers::send(['ORDER_ID' => $order_id, 'STATUS' => strtoupper(trim($status)), 'HOSTNAME' => SmartUTM::getMyHostname()], 'status/change');
            } catch (\Exception $e) {
//                echo 'Error: ',  $e->getMessage();
                Helpers::log('Sending Status Error: ' . $e->getMessage());
                Helpers::sendError($e->getMessage());
            }
        }
    }

    /**
     * When CRM Send to Site needle status
     */
    private static function changeStatusOnSite()
    {
        $api_token = Settings::getOption('lead_api');
        // Helpers::log("Status changed by CRM! CMS token: {$api_token}", $_REQUEST);
        $status = strtolower(sanitize_text_field($_REQUEST['status'] ?? 'NEW'));
        $order_id = intval($_REQUEST['order_id'] ?? 0);
        $hash = $_REQUEST['hash'] ?? '';

        // For debugging
        // dd(md5($api_token . '_' . strtoupper($status)));

        if (($_REQUEST['flamix_status'] ?? 'N') == 'Y' && $status && ($order_id > 0) && $hash && !empty($api_token) && $hash == md5($api_token . '_' . strtoupper($status))) {
            $all_status = array_keys(wc_get_order_statuses());
            $wc_status = str_replace('_', '-', "wc-{$status}"); // ON_HOLD -> wc-on-hold

            if (!in_array($wc_status, $all_status)) {
                Helpers::log("Status {$wc_status} not found in all WP statuses!", $all_status);
                dd("Status {{$wc_status}} not found!", $all_status); // 500 error
            }

            Helpers::log("In status {$wc_status}", $all_status);

            // Block looping
            define('FLAMIX_STATUS_CHANGED', true);
            // Updating status...
            (new WC_Order($order_id))->update_status($status, 'Bitrix24 status changed');
            // Action (Can add custom logic)
            do_action('flamix_bitrix24_integrations_status_changed', $status, $order_id);

            if (is_array($_REQUEST['products'] ?? null)) {
                Products::mergeProducts($order_id, $_REQUEST['products']);
            }

            die('Status was changed!'); // 200 OK
        }
    }
}